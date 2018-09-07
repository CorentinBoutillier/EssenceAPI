<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthController extends AbstractController
{
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $em = $this->getDoctrine()->getManager();

        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $lastname = $request->request->get('_lastname');
        $firstname = $request->request->get('_firstname');

        if (!is_null($em->getRepository(Entity\User::class)->createQueryBuilder('u')
            ->select('u.id')
            ->where('LOWER(u.username) =:usernameTest')
            ->setParameter('usernameTest', strtolower($username))
            ->getQuery()
            ->getOneOrNullResult())
        ) {
            return $this->json([
                'error' => '409',
                'message' => 'Invalid username, this username already exist',
            ], 409);
        }

        $user = new Entity\User($username);
        $user->setPassword($encoder->encodePassword($user, $password));
        $user->setLastName($lastname);
        $user->setFirstName($firstname);
        $em->persist($user);
        $em->flush();

        //return $response;
        return new Response(json_encode('User %s successfully created'. $user->getUsername()), 200);
    }

    public function api()
    {
        return new Response(sprintf('Logged in as %s', $this->getUser()->getUsername()));
    }
}