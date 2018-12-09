<?php
declare(strict_types=1);

namespace App\authenticators;

use Nette\Database\Context;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use function password_verify;

class DatabaseAuthenticator implements IAuthenticator
{
	/** @var Context */
	private $database;

	public function __construct(Context $database)
	{
		$this->database = $database;
	}

	public function authenticate(array $credentials): Identity
	{
		list($username, $password) = $credentials;

		$row = $this->database->table('users')
			->where('username', $username)->fetch();

		if (!$row) {
			throw new AuthenticationException('User not found.');
		}

		if (!password_verify($password, $row->password)) {
			throw new AuthenticationException('Invalid password.');
		}

		return new Identity($row->id, null, ['username' => $row->username]);
	}
}
