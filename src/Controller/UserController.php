<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSettings;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Knp\Component\Pager\PaginatorInterface;

class UserController extends AbstractController
{
    /**
     * Get all users along with their custom settings
     *
     * This call does not currently take into account active or status columns
     *
     * @Route("/users/{limit}/{page}", methods={"GET"})
     * @SWG\Response(
     *  response=200,
     *  description="Returns all user info including custom settings",
     * )
     * @SWG\Parameter(
     *  name="limit",
     *  type="integer",
     *  required=true,
     *  in="path",
     *  description="Limit records per page, maximum value = 50"
     * )
     * @SWG\Parameter(
     *  name="page",
     *  type="integer",
     *  required=true,
     *  in="path",
     *  description="What page to return"
     * )
     *
     */
    public function getUsers(UserRepository $userRepository, PaginatorInterface $paginator, Request $request, $limit, $page): Response
    {
        $limit = $limit < 50 ? $limit : 50;
        $query = $userRepository->findAll();

        $requests = $paginator->paginate(
            $query,
            $request->query->get('page', $page),
            $limit
        );

        $serializer = $this->container->get('serializer');
        $json = $serializer->serialize($requests, 'json');

        return new Response($json);
    }

    /**
     * Get a single user with settings by id
     *
     * This call doesn't take into account activity or status
     *
     * @Route("/user/{id}", methods={"GET"})
     * @SWG\Response(
     *  response=200,
     *  description="Returns single user information and settings",
     * )
     * @SWG\Parameter(
     *  name="id",
     *  type="integer",
     *  required=true,
     *  in="path",
     *  description="Id of user you are requesting",
     * )
     */
    public function getSingleUser(Request $request, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);
        $serializer = $this->container->get('serializer');
        $json = $serializer->serialize($user, 'json');

        return new Response($json);
    }

    /**
     * Add a new user with or without custom settings
     *
     * This call allows you to post a settings object to populate custom settings for new user
     *
     * @Route("/user", methods={"POST"})
     * @SWG\Response(
     *  response=200,
     *  description="Returns user information that was inserted back",
     * )
     * @SWG\Parameter(
     *  name="body",
     *  type="array",
     *  required=true,
     *  in="body",
     *  description="attributes to add to new user, settings added as key/values in 'settings' object",
     *  @SWG\Schema(
     *      type="object",
     *      properties={
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="email", type="string"),
     *          @SWG\Property(property="active_status", type="string"),
     *          @SWG\Property(property="settings", type="object",
     *              properties={
     *                  @SWG\Property(property="name", type="string"),
     *                  @SWG\Property(property="value", type="string"),
     *              }
     *          ),
     *      }
     *  )
     * )
     */
    public function add(Request $request): Response
    {
        $user = new User;
        $data = json_decode($request->getContent(), true);
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setActiveStatus($data['active_status']);
        $user->setCreatedAt();
        $user->setUpdatedAt();

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $userID = $user->getId();

        if ($data['settings']) {

            //this doesn't allow for more than one name/value pair, but it should
            $userSettings = new UserSettings;
            $userSettings->setName($data['settings']['name']);
            $userSettings->setValue($data['settings']['value']);
            $userSettings->setUid($userID);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($userSettings);
            $em->flush();
        }

        return new Response($request->getContent());
    }

    /**
     * Update user information, user id required
     *
     * This call allows you to post a settings object to populate custom settings for new user
     *
     * @Route("/user/{id}", methods={"PUT"})
     * @SWG\Response(
     *  response=200,
     *  description="Returns user information of user being updated",
     * )
     * @SWG\Parameter(
     *  name="id",
     *  type="integer",
     *  required=true,
     *  in="path",
     *  description="Id of user you are updating"
     * )
     * @SWG\Parameter(
     *  name="body",
     *  type="array",
     *  required=true,
     *  in="body",
     *  description="user attributes to modify",
     *  @SWG\Schema(
     *      type="object",
     *      properties={
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="email", type="string"),
     *          @SWG\Property(property="active_status", type="string"),
     *      }
     *  )
     * )
     */
    public function edit(Request $request, UserRepository $userRepository, $id): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $userRepository->find($id);

        if (empty($user)) {
            return new Response('There is no user with this id. Please try again');
        } else {
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setActiveStatus($data['active_status']);
            $user->setUpdatedAt();

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return new Response($request->getContent());
        }
    }

    /**
     * Delete a single user and their settings by id
     *
     * @Route("/user/{id}", methods={"DELETE"})
     * @SWG\Response(
     *  response=200,
     *  description="User deleted",
     * )
     * @SWG\Parameter(
     *  name="id",
     *  type="integer",
     *  required=true,
     *  in="path",
     *  description="Id of user you are deleting",
     * )
     */
    public function delete(Request $request, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);
        if (empty($user)) {
            return new Response('There is no user with this id. Please try again');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return new Response($id);
    }
}
