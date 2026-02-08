<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BookingRepository;
use App\Repository\SessionRepository;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;


class AdminController extends AbstractController {


    #[Route('/admin/board', name: 'booking_session')]
    public function getBookingAndSessions(BookingRepository $bookingRepository, SessionRepository $sessionRepository ): Response
    {
        // Get all bookings using your repository method
        $bookings = $bookingRepository->findAllBookings();
        // Get all Sessions using respository session method
        $sessions = $sessionRepository->findAllSessions();


        return $this->render('/Back-office/board-list.html.twig', [
            'bookings' => $bookings,
            'Sessions' => $sessions
        ]);
    }



    
    #[Route('/admin/session/update', name: 'admin_session_update', methods: ['POST'])]
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

        return $this->redirectToRoute('booking_session');
    }


    #[Route('/admin/Session/delete', name: 'admin_session_delete', methods: ['POST'])]
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

        return $this->redirectToRoute('booking_session');
    }



    #[Route('/admin/bookings/update', name: 'admin_booking_update', methods: ['POST'])]
    public function updateBooking(
        Request $request,
        BookingRepository $bookingRepository
    ): Response {

        $booking = $bookingRepository->find($request->request->get('id'));

        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        $booking->setFirstName($request->request->get('firstName'));
        $booking->setLastName($request->request->get('lastName'));
        $booking->setUserEmail($request->request->get('userEmail'));
        $booking->setPhoneNumber($request->request->get('phoneNumber'));

        $bookingRepository->save($booking, true);

        return $this->redirectToRoute('booking_session');
    }


    #[Route('/admin/booking/delete', name: 'admin_booking_delete', methods: ['POST'])]
    public function deleteBooking(
        Request $request,
        BookingRepository $bookingRepository,
        EntityManagerInterface $em
    ): Response {
        $id = $request->request->get('id');
        $booking = $bookingRepository->find($id);

        if (!$booking) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_booking_' . $id, $request->request->get('_token'))) {
            $em->remove($booking);
            $em->flush();

            $this->addFlash('success', 'Booking deleted successfully');
        }

        return $this->redirectToRoute('booking_session');
    }






}