<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Request\ResetPasswordRequest;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends ApiController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function requestPasswordResetAction(Request $request, MailerInterface $mailer): Response
    {
        $requestData = json_decode($request->getContent(),true);
        $resetPasswordRequest = new ResetPasswordRequest();
        $form = $this->createForm(ResetPasswordRequestFormType::class, $resetPasswordRequest);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        return $this->processSendingPasswordResetEmail(
            $resetPasswordRequest->email,
            $mailer
        );
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): JsonResponse
    {
        try {
            $user = $this->userRepository->findOrFail($emailFormData);
        } catch (EntityNotFoundException) {
            // Do not reveal whether a user account was found or not.
            return $this->respondWithSuccessMessage('Please check your email for further instructions.');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (TooManyPasswordRequestsException) {
            return $this
                ->setStatusCode(400)
                ->respondWithError(
                    'TOO_MANY_REQUESTS',
                    'There have been too many password reset requests for this account. Please try again later.'
                );
        } catch (ResetPasswordExceptionInterface|\Throwable $e) {
            return $this->respondInternalServerError($e);
        }

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('SGGW MeetApp - Your Reset Password Request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'username' => $user->getFirstName().' '.$user->getLastName(),
                'resetToken' => $resetToken,
            ]);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            return $this->setStatusCode(500)->respondWithError(
                'INTERNAL_SERVER_ERROR',
                'Internal server error. Unable to send reset password email.'
            );
        }
        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);
        return $this->respondWithSuccessMessage('Please check your email for further instructions.');
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    public function handlePasswordResetAction(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        string $token = null
    ): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_handle_reset_password', ['token' => ''], 307);
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            return $this->respondNotFound('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getReason());
            return $this->respondInternalServerError($e);
        }

        // The token is valid; allow the user to change their password.
        $requestData = json_decode($request->getContent(), true);
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
        // A password reset token should be used only once, remove it.
        $this->resetPasswordHelper->removeResetRequest($token);

        $encodedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
        $user->setPassword($encodedPassword);
        $this->userRepository->updateUserPassword($user, $encodedPassword);

        // The session is cleaned up after the password has been changed.
        $this->cleanSessionAfterReset();
        return $this->respondWithSuccessMessage('Password was changed successfully.');
    }
}
