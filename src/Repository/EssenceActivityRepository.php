<?php
/**
 * Created by PhpStorm.
 * User: corentinboutillier
 * Date: 07/09/2018
 * Time: 23:10
 */

namespace App\Repository;


class EssenceActivityRepository extends \Doctrine\ORM\EntityRepository
{

    public function getStatsBetweenDates($dateStart, $dateEnd) {
        $results = $this->createQueryBuilder('a')
            ->where('a.date BETWEEN :dateStart AND :dateEnd')
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
            ->getQuery()->getResult();

        $datas = [];
        foreach ($results as $result) {
            $data = new \stdClass();
            $data->date = $result->getDate();
            $data->count = $result->getCount();
            array_push($datas, $data);
        }


        return $datas;
    }

}
