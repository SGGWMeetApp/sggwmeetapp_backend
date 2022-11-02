<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\RegistrationType;
use App\Request\RegisterUserRequest;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends ApiController
{
    public function register(Request $request)
    {
        $requestData = json_decode($request->getContent(),true);
        $registrationRequest = new RegisterUserRequest();
        $form = $this->createForm(RegistrationType::class, $registrationRequest);
        $form->submit($requestData);

        if (!$form->isValid()) {
            throw new FormException($form);
        }

        dd($registrationRequest);

    }
}