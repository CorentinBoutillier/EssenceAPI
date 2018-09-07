<?php
/**
 * Created by PhpStorm.
 * User: corentinboutillier
 * Date: 07/09/2018
 * Time: 22:58
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="essenceActivity")
 * @ORM\Entity(repositoryClass="App\Repository\EssenceActivityRepository")
 */
class EssenceActivity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \Date
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var int|null
     *
     * @ORM\Column(name="count", type="integer")
     */
    private $count;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \Date $date
     */
    public function setDate(\Date $date)
    {
        $this->date = $date;
    }

    /**
     * @return int|null
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int|null $count
     */
    public function setCount(?int $count)
    {
        $this->count = $count;
    }

}