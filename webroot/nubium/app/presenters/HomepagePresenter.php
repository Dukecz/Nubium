<?php
declare(strict_types=1);

namespace App\Presenters;

use App\forms\BaseFormFactory;
use function in_array;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\DriverException;
use Nette\Security\AuthenticationException;
use Nette\Utils\Paginator;
use function password_hash;
use stdClass;

final class HomepagePresenter extends Presenter
{
	/** @var Context @inject */
	public $db;

	public function renderDefault(int $page = 1): void
	{
		if ($this->getUser()->isLoggedIn()) {
			$numberOfArticles = $this->db->fetchField('SELECT COUNT(*) FROM articles');
			$paginator = $this->getPaginator($numberOfArticles, $page);
			$articles = $this->db->fetchAll('SELECT * FROM articles LIMIT ? OFFSET ?', $paginator->getLength(), $paginator->getOffset());
		} else {
			$numberOfArticles = $this->database->fetchField('SELECT COUNT(*) FROM articles WHERE registeredOnly = 0');
			$paginator = $this->getPaginator($numberOfArticles, $page);
			$articles = $this->db->fetchAll('SELECT * FROM articles WHERE registeredOnly = 0', $paginator->getLength(), $paginator->getOffset());
		}

		$this->template->add('articles', empty($articles) ? [] : $articles);
		$this->template->add('paginator', $paginator);
	}

	protected function getPaginator(int $totalCount, int $page): Paginator
	{
		$paginator = new Paginator();
		$paginator->setItemCount($totalCount);
		$paginator->setItemsPerPage(1);
		$paginator->setPage($page);

		return $paginator;
	}

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
			try {
				$this->getUser()->login($values->username, $values->password);
				$this->redirect('Homepage:');
			} catch (AuthenticationException $e) {
				$this->flashMessage('Username or password is incorrect.', 'danger');
			}
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
				$this->flashMessage('Registration failed', 'danger');
			}
			$this->redirect('Homepage:');
		};

		BaseFormFactory::bootstrapize($form);

		return $form;
	}

	public function createComponentProfileForm(): Form
	{
		$form = new Form();
		$form->addPassword('password', 'Password')
			->setRequired()
			->addRule(Form::MIN_LENGTH, 'Password must have at least 8 characters.', 8)
			->addRule(Form::PATTERN, 'Password must contain at least one letter.', '[a-zA-Z]+')
		;

		$form->addPassword('passwordConfirm', 'Confirm password')
			->setRequired()
			->addRule(Form::EQUAL, 'Passwords doesn\'t match.', $form['password'])
		;

		$form->addSubmit('update', 'Update');

		$form->onSuccess[] = function (Form $form, stdClass $values) {
			$result = $this->db->query('UPDATE users SET',
				[
					'password' => password_hash($values->password, \PASSWORD_BCRYPT),
				],
				'WHERE id = ?',
				$this->getUser()->getId()
			);
			if ($result->getRowCount() === 1) {
				$this->flashMessage('Update successful', 'success');
			} else {
				$this->flashMessage('Update failed', 'danger');
			}
			$this->redirect('Homepage:');
		};

		BaseFormFactory::bootstrapize($form);

		return $form;
	}

	protected function registerUser(string $username, string $password, string $email): bool
	{
		try {
			$this->db->query('INSERT INTO users',
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

	public function renderVote(): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			return;
		}

		$id = $this->getRequest()->getPost('id');
		$vote = $this->getRequest()->getPost('vote');

		if (!empty($id) && !empty($vote) && in_array($vote, ['plus', 'minus'])) {
			switch ($vote) {
				case 'plus':
					$this->db->query('UPDATE articles SET rank = rank + 1 WHERE id = ?', $id);
					break;
				default:
					$this->db->query('UPDATE articles SET rank = rank - 1 WHERE id = ?', $id);
					break;
			}
			$this->sendJson($this->db->fetchField('SELECT rank FROM articles WHERE id = ?', $id));
		}
	}
}
