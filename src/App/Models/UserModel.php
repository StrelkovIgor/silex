<?php

namespace App\Models;

use Doctrine\DBAL\Connection;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Validator;
use App\Service\AuthService;

/**
 * User repository
 */
class UserModel implements ModelInterface, UserProviderInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * @var \Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder
     */
    protected $encoder;
	
	protected $auth_config;

    public function __construct(Connection $db, $encoder, $auth_config = array())
    {
        $this->db = $db;
        $this->encoder = $encoder;
		$this->auth_config = $auth_config;
    }

    /**
     * Saves the user to the database.
     *
     * @param \Project\Entity\User $user
     */
    public function save($user, $role = 'ROLE_USER')
    {
        $userData = array(
            'username' => $user->getUsername(),
            'mail' => $user->getMail(),
            'role' => $role,
        );

        if (strlen($user->getPassword()) != 88)
		{
            $userData['salt'] = uniqid(mt_rand());
            $userData['password'] = $this->encoder->encodePassword($user->getPassword(), $userData['salt']);
        }

        if ($user->getId())
		{
            $newFile = $this->handleFileUpload($user);
            if ($newFile) {
                $userData['image'] = $user->getImage();
            }

            $this->db->update('users', $userData, array('id' => $user->getId()));
        } else {

            $userData['created_at'] = time();

            $this->db->insert('users', $userData);

            $id = $this->db->lastInsertId();
            $user->setId($id);
			$auth = new AuthService($this->auth_config);
			$reg = $auth->sendService($userData,'reg');
        }
    }
	
	public function saveService($user)
	{
		$userData = array(
			'username' 	=> $user['username'],
			'mail'		=> $user['mail'],
			'salt'		=> $user['salt'],
			'role'		=> 'ROLE_USER',
			'password'	=> $user['password'],
			'created_at'=> $user['created_at']?$user['created_at']:time()
		);
		$this->db->insert('users', $userData);
	}

    /**
     * Deletes the user.
     *
     * @param integer $id
     */
    public function delete($id)
    {
        return $this->db->delete('users', array('id' => $id));
    }

    /**
     * Returns the total number of users.
     *
     * @return integer The total number of users.
     */
    public function getCount()
	{
        return $this->db->fetchColumn('SELECT COUNT(id) FROM users');
    }

    /**
     * Returns a user matching the supplied id.
     *
     * @param integer $id
     *
     * @return \Project\Entity\User|false An entity object if found, false otherwise.
     */
    public function find($id)
    {
        $userData = $this->db->fetchAssoc('SELECT * FROM users WHERE id = ?', array($id));
        return $userData ? $this->buildUser($userData) : FALSE;
    }

    /**
     * Returns a collection of users.
     *
     * @param integer $limit
     *   The number of users to return.
     * @param integer $offset
     *   The number of users to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of users, keyed by user id.
     */
    public function findAll($limit, $offset = 0, $orderBy = array())
    {
        if (!$orderBy) {
            $orderBy = array('username' => 'ASC');
        }

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('u.*')
            ->from('users', 'u')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('u.' . key($orderBy), current($orderBy));
        $statement = $queryBuilder->execute();
        $usersData = $statement->fetchAll();

        $users = array();
        foreach ($usersData as $userData) {
            $userId = $userData['id'];
            $users[$userId] = $this->buildUser($userData);
        }

        return $users;
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername($username,$req = true)
    {
		$user = new User();
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('u.*')
            ->from('users', 'u')
            ->where('u.username = :username OR u.mail = :mail')
            ->setParameter('username', $username)
            ->setParameter('mail', $username)
            ->setMaxResults(1);
        $statement = $queryBuilder->execute();
        $usersData = $statement->fetchAll();
        if (count($usersData)) {
			$user = $this->buildUser($usersData[0]);
        }else{
			if($req)
			{
				$auth = new AuthService($this->auth_config);
				$message = $auth->checkUser($username, true);
				if(count($message))
				{
					if($message['code'] == 201)
					{
						$this->saveService([
							'username'	=> $message['message'][0],
							'salt'		=> $message['message'][1],
							'password'	=> $message['message'][2],
							'mail'		=> $message['message'][3]
						]);
					}
					return $this->loadUserByUsername($username, false);
				}
				
			}
		}
        
        return $user;
    }
	
	/**
     * {@inheritDoc}
     */
	public function isUser($username)
	{
		return $this->loadUserByUsername($username, false)->getId()!=NULL;
	}

    /**
     * {@inheritDoc}
     */
	 
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }
        $id = $user->getId();
        $refreshedUser = $this->find($id);
        if (false === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($id)));
        }
		
        return $refreshedUser;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return 'App\Entity\User' === $class;
    }

    /**
     * Instantiates a user entity and sets its properties using db data.
     *
     * @param array $userData
     *   The array of db data.
     *
     * @return \Project\Entity\User
     */
    protected function buildUser($userData)
    {
        $user = new User();
        $user->setId($userData['id']);
        $user->setUsername($userData['username']);
        $user->setSalt($userData['salt']);
        $user->setPassword($userData['password']);
        $user->setMail($userData['mail']);
        $user->setRole($userData['role']);
        $createdAt = new \DateTime('@' . $userData['created_at']);
        $user->setCreatedAt($createdAt);
        return $user;
    }
	
	public function isValid($user, $form)
	{
		
		if($this->isUser($user->getUsername()))
			$form->addError(new FormError('This user exists'));
		
		if($user->getPassword() == null)
			$form->addError(new FormError('Passwords do not match'));
		
		return $form;
	}
}
