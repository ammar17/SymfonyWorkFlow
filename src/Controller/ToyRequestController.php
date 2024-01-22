<?php

namespace App\Controller;

use App\Entity\ToyRequest;
use App\Form\ToyRequestType;
use App\Repository\ToyRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ToyRequestController extends AbstractController {

	/** @var WorkflowInterface $toyRequestWorkflow */
	private $toyRequestWorkflow;


	public function __construct(WorkflowInterface $toyRequestWorkflow) {
		$this->toyRequestWorkflow = $toyRequestWorkflow;
	}

	/**
	 * @Route("/new", name="app_new")
	 */
	public function index(Request $request, EntityManagerInterface $entityManager): Response {
		$toyRequest = new ToyRequest();

		$toyRequest->setUser($this->getUser());

		$form = $this->createForm(ToyRequestType::class, $toyRequest);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {

			$toyRequest = $form->getData();

			try {
				$this->toyRequestWorkflow->apply($toyRequest, 'to_pending');
			} catch (\Exception $e) {
				$this->addFlash('error', 'Something went wrong');
				return $this->redirectToRoute('app_home');
			}
			$entityManager->persist($toyRequest);
			$entityManager->flush();


			$this->addFlash('success', 'Demande de jouet enregistrée !');

			// Faire une nouvelle demande
			return $this->redirectToRoute('app_new');

		}

		return $this->render('toy_request/index.html.twig', ['form' => $form->createView(),]);
	}

	/**
	 * @Route("/parent", name="app_parent")
	 *
	 * @param ToyRequestRepository $repository
	 *
	 * @return Response
	 */
	public function parent(ToyRequestRepository $repository): Response {
		$toyRequests = $repository->findAll();

		return $this->render('toy_request/parent.html.twig', ['toys' => $toyRequests]);

	}

	/**
	 * @Route("/change/{id}/{to}", name="app_change")
	 */
	public function change(string $id, String $to, EntityManagerInterface $entityManager, ToyRequestRepository $repository): Response
	{
		/** @var ToyRequest $toyRequest */
		$toyRequest = $repository->findOneBy(['id' => $id]);
		try {
			$this->toyRequestWorkflow->apply($toyRequest, $to);
		} catch (\LogicException $exception) {
			//
		}

		$entityManager->persist($toyRequest);
		$entityManager->flush();

		$this->addFlash('success', 'Action enregistrée !');

		return $this->redirectToRoute('app_parent');
	}
}
