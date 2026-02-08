<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\SessionRepository;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;




class SessionController extends AbstractController {
#[Route('/Session', name: 'Session_create')]
public function new(
    Request $request,
    SessionRepository $SessionRepository
): Response
        {

            if ($request->isMethod('post')) {   
                $Session = new Session();

                $Session->setName($request->request->get('name'));

                $dateString = $request->request->get('date');

                if ($dateString) {
                    $date = new \DateTime($dateString);
                    $Session->setDate($date);
                }
                $Session->setDuration($request->request->get('duration'));
                $Session->setSessionDescription($request->request->get('sessionDescription'));
                $Session->setLevel($request->request->get('level'));

                $SessionRepository->save($Session);

                return $this->redirectToRoute('all_Sessions'); 
            }

            return $this->render('/Front-office/session/add-session.html.twig');
        }

    #[Route('/Sessions', name: 'all_Sessions')]
    public function getAllSessions(SessionRepository $SessionRepository): Response
    {
        // Get all Sessions using your repository method
        $Sessions = $SessionRepository->findAllSessions();
        // Render the template and pass the Sessions
        return $this->render('/Front-office/session/sessionList.html.twig', [
            'Sessions' => $Sessions
        ]);
    }

    #[Route('/teacher/sessions', name: 'teacher_session')]
    public function getTeacherSessions(SessionRepository $SessionRepository): Response
    {
        // Get all Sessions using your repository method
        $Sessions = $SessionRepository->findAllSessions();
        // Render the template and pass the Sessions
        return $this->render('/Front-office/session/teacher-session-list.html.twig', [
            'Sessions' => $Sessions
        ]);
    }


    #[Route('/session/update', name: 'session_update', methods: ['POST'])]
    public function update(
        Request $request,
        SessionRepository $SessionRepository
    ): Response {

        $Session = $SessionRepository->find($request->request->get('id'));

        if (!$Session) {
            throw $this->createNotFoundException('Session not found');
        }

        $Session->setName($request->request->get('name'));
        $Session->setLevel($request->request->get('level'));
        $dateString = $request->request->get('date');
        if ($dateString) {
            $Session->setDate(new DateTimeImmutable($dateString));
        }
        $Session->setSessionDescription($request->request->get('sessionDescription'));
        $Session->setDuration($request->request->get('duration'));


        $SessionRepository->save($Session, true);

        return $this->redirectToRoute('teacher_session');
    }


    #[Route('/Session/delete', name: 'Session_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        SessionRepository $SessionRepository,
        EntityManagerInterface $em
    ): Response {
        $id = $request->request->get('id');
        $Session = $SessionRepository->find($id);

        if (!$Session) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_Session_' . $id, $request->request->get('_token'))) {
            $em->remove($Session);
            $em->flush();

            $this->addFlash('success', 'Session deleted successfully');
        }

        return $this->redirectToRoute('teacher_session');
    }


}
