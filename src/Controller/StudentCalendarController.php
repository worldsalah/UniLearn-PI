<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StudentCalendarController extends AbstractController
{
    #[Route('/student/calendar', name: 'student_calendar')]
    public function index(Request $request, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2020 || $year > 2030) {
            $year = (int) date('Y');
        }

        $startDate = new \DateTime("$year-$month-01");
        $startDate->setTime(0, 0, 0);
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);

        $bookings = $bookingRepository->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.preferredDate >= :startDate')
            ->andWhere('b.preferredDate <= :endDate')
            ->andWhere('b.status != :deniedStatus')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('deniedStatus', 'denied')
            ->orderBy('b.preferredDate', 'ASC')
            ->addOrderBy('b.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $bookingsByDate = [];
        foreach ($bookings as $booking) {
            $dateKey = $booking->getPreferredDate()->format('Y-m-d');
            if (!isset($bookingsByDate[$dateKey])) {
                $bookingsByDate[$dateKey] = [];
            }
            $bookingsByDate[$dateKey][] = $booking;
        }

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        return $this->render('Front-office/booking/student_calendar.html.twig', [
            'bookingsByDate' => $bookingsByDate,
            'currentMonth' => $month,
            'currentYear' => $year,
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
            'monthName' => date('F', mktime(0, 0, 0, $month, 1) ?: null),
        ]);
    }
}
