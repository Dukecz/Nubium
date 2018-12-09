<?php
declare(strict_types=1);

namespace App\Presenters;

use App\forms\BaseFormFactory;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\DriverException;
use function password_hash;
use stdClass;

final class HomepagePresenter extends Presenter
{
	/** @var Context @inject */
	public $db;

	public function createComponentLoginForm(): Form
	{
		$form = new Form();
		$form->addText('username', 'Username')
			->setRequired()
		;

		$form->addPassword('password', 'Password')
			->setRequired()
		;

		$form->addSubmit('login', 'Login');

		$form->onSuccess[] = function (Form $form, stdClass $values) {
			$this->getUser()->login($values->username, $values->password);
		};

		BaseFormFactory::bootstrapize($form);

		return $form;
	}

	public function createComponentRegisterForm(): Form
	{
		$form = new Form();
		$form->addText('username', 'Username')
			->setRequired()
			->addRule(Form::MAX_LENGTH, 'Username is too long.', 255);
		;

		$form->addEmail('email', 'Email')
			->setRequired()
			->addRule(Form::MAX_LENGTH, 'Email is too long.', 255);
		;

		$form->addPassword('password', 'Password')
			->setRequired()
			->addRule(Form::MIN_LENGTH, 'Password must have at least 8 characters.', 8)
			->addRule(Form::PATTERN, 'Password must contain at least one letter.', '[a-zA-Z]+')
		;

		$form->addPassword('passwordConfirm', 'Confirm password')
			->setRequired()
			->addRule(Form::EQUAL, 'Passwords doesn\'t match.', $form['password'])
		;

		$form->addSubmit('login', 'Login');

		$form->onSuccess[] = function (Form $form, stdClass $values) {
			$result = $this->registerUser($values->username, $values->password, $values->email);
			if ($result) {
				$this->flashMessage('Registration successful', 'success');
			} else {
				$this->flashMessage('Registration failed', 'error');
			}
			$this->redirect('Homepage:');
		};

		BaseFormFactory::bootstrapize($form);

		return $form;
	}

	protected function registerUser(string $username, string $password, string $email): bool
	{
		try {
			$this->db->query('INSERT INTO users ?',
				[
					'username' => $username,
					'password' => password_hash($password, \PASSWORD_BCRYPT),
					'email' => $email,
					'ip' => $_SERVER['REMOTE_ADDR'],
				]
			);
		} catch (DriverException $e) {
			return false;
		}

		return true;
	}

	/**
	 * @throws \Nette\Application\AbortException
	 */
	public function renderLogout(): void
	{
		$this->getUser()->logout();
		$this->redirect('Homepage:');
	}
}
