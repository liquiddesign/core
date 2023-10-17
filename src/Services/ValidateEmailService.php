<?php

namespace Base\Services;

use Base\Bridges\AutoWireService;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Nette\Utils\Validators;

class ValidateEmailService implements AutoWireService
{
	protected EmailValidator $validator;

	protected MultipleValidationWithAnd $weakConditions;

	protected MultipleValidationWithAnd $strictConditions;

	public function __construct()
	{
		$this->validator = new EmailValidator();
		$this->weakConditions = new MultipleValidationWithAnd([
			new RFCValidation(),
		]);
		$this->strictConditions = new MultipleValidationWithAnd([
			new RFCValidation(),
			new DNSCheckValidation(),
		]);
	}

	/**
	 * @deprecated If possible, use "validate"
	 */
	public function validateWeakly(string $email): bool
	{
		\trigger_deprecation('liquiddesign/package', '2.0', 'Using "%s" is deprecated, use "%s" instead.', 'validateWeakly', 'validate');

		return Validators::isEmail($email) && $this->validator->isValid($email, $this->weakConditions);
	}

	public function validate(string $email): bool
	{
		return Validators::isEmail($email) && $this->validator->isValid($email, $this->strictConditions);
	}
}
