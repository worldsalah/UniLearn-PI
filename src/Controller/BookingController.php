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




class BookingController extends AbstractController {
#[Route('/booking', name: 'booking_create')]
public function new(
    Request $request,
    BookingRepository $bookingRepository,
    SessionRepository $sessionRepository
): Response {
    //  Get all sessions BEFORE submit
    $sessions = $sessionRepository->findAllSessions();

    if ($request->isMethod('POST')) {

        $booking = new Booking();

        $booking->setFirstName($request->request->get('firstName'));
        $booking->setLastName($request->request->get('lastName'));
        $booking->setUserEmail($request->request->get('userEmail'));
        $booking->setPhoneNumber($request->request->get('phoneNumber'));

        // Get selected session id from form
        $sessionId = $request->request->get('session_id');

        // Find Session entity
        $session = $sessionRepository->find($sessionId);

        if ($session) {
            $booking->setSession($session);
        }


        $bookingRepository->save($booking);

        return $this->redirectToRoute('booking_create');
    }

     return $this->render('Front-office/booking/index.html.twig', [
        'sessions' => $sessions
    ]);
}

    #[Route('/bookings', name: 'all_bookings')]
    public function getAllBookings(BookingRepository $bookingRepository): Response
    {
        // Get all bookings using your repository method
        $bookings = $bookingRepository->findAllBookings();
        // Render the template and pass the bookings
        return $this->render('/Front-office/booking/bookingList.html.twig', [
            'bookings' => $bookings
        ]);
    }




    #[Route('/bookings/update', name: 'booking_update', methods: ['POST'])]
    public function update(
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

        return $this->redirectToRoute('all_bookings');
    }


    #[Route('/booking/delete', name: 'booking_delete', methods: ['POST'])]
    public function delete(
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

        return $this->redirectToRoute('all_bookings');
    }


}