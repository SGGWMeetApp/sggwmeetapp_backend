<?php


namespace App\Exception;


use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;


class FormException extends HttpException
{
    protected FormInterface $form;

    public function __construct(FormInterface $form, int $statusCode = 400, ?string $message = 'Form Exception', ?\Exception $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
        $this->form = $form;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getErrors(): FormErrorIterator
    {
        return $this->form->getErrors(true);
    }
}