<?php
/**
 * Created by PhpStorm.
 * User: corentinboutillier
 * Date: 29/08/2018
 * Time: 22:47
 */

namespace App\Controller;

use JsonSchema\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity;

class EssenceController extends Controller
{

    /**
     * @Rest\View()
     * @Rest\Get("/essence/long/{longitude}/lat/{latitude}/dist/{distance}/carburant/{carburant}/data")
     */
    public function getEssenceDataAction(Request $request, $longitude, $latitude, $distance, $carburant) {

        $lastWeek = new \DateTime('-1 week');

        if (file_exists('data/essence/PrixCarburants_instantane.xml')) {

            $fileDate = new \DateTime();
            $fileDate->setTimestamp(filemtime('data/essence/PrixCarburants_instantane.xml'));
            $interval = new \DateInterval("PT10M");
            $now = new \DateTime();
            $now->sub($interval);

            if ($now > $fileDate) {
                $xml = $this->downloadDataFile();
            } else {
                $xml = simplexml_load_file('data/essence/PrixCarburants_instantane.xml');
            }
        } else {
            $xml = $this->downloadDataFile();
        }

        $datas = [];
        foreach ($xml->pdv as $pdv){
            foreach($pdv->attributes() as $a => $b) {
                if ($a === 'latitude') {
                    $latitude2 = $b;
                } elseif ($a === 'longitude') {
                    $longitude2 = $b;
                }
            }
            $dist = $this->distance(
                (float)$latitude,
                (float)$longitude,
                (float)$latitude2 / 100000,
                (float)$longitude2 / 100000,
                "K");

            if ($dist <= $distance) {
                array_push($datas, $pdv);
            }
        }


        // Tri
        $result = [];
        $datasSize = sizeof($datas);

        for ($i = 0; $i < $datasSize; $i++) {
            $bool = FALSE;
            foreach ($datas[$i]->prix as $prixUnique) {
                if ((string)$prixUnique->attributes()['nom'] === $carburant) {
                    $val = (string)$prixUnique->attributes()['valeur'];
                    $bool = TRUE;
                }
            }
            if ($bool) {
                if ($i === 0) {
                    array_push($result, $this->createObj($datas[0], $longitude, $latitude));
                } else {
                    $resultSize = sizeof($result);
                    $bool2 = TRUE;
                    for ($j = 0; $j < $resultSize; $j++) {
                        foreach ($result[$j]->prix as $prixUnique) {
                            $maj = new \DateTime((string)$prixUnique->maj);
                            if ((string)$prixUnique->nom === $carburant && $maj > $lastWeek) {
                                $valJ = (string)$prixUnique->valeur;
                                $targetId = $j;
                                break;
                            }
                        }
                        if (isset($targetId) && (float)$val < (float)$valJ && $bool2) {
                            array_splice( $result, $targetId, 0, [$this->createObj($datas[$i], $longitude, $latitude)]);
                            $bool2 = FALSE;
                        }
                    }
                    if (!isset($targetId) || ($targetId === sizeof($result)-1 && $bool2)) {
                        array_push($result, $this->createObj($datas[$i], $longitude, $latitude));
                    }

                }
            }
        }



        return new Response(json_encode($result));
    }

    private function createObj($datas, $longitudeBase, $latitudeBase) {
        $item = new \stdClass();
        $item->id = (string)$datas->attributes()['id'];
        $item->latitude = (string)$datas->attributes()['latitude'];
        $item->longitude = (string)$datas->attributes()['longitude'];
        $item->adresse =(string) $datas->adresse;
        $item->cp = (string)$datas->attributes()['cp'];
        $item->ville = (string)$datas->ville;

        $item->distance = $this->distance((float)$datas->attributes()['latitude'] / 100000, (float)$datas->attributes()['longitude'] / 100000, (float)$latitudeBase, (float)$longitudeBase, 'K');

        $horaires = new \stdClass();
        if (sizeof($datas->horaires) > 0) {
            $horaires->automate = ((string)$datas->horaires->attributes()['automate-24-24'] === "" ? "1" : "0");

            $jours = [];
            foreach ($datas->horaires->jour as $jourUnique) {
                $jour = new \stdClass();
                $jour->nom = (string)$jourUnique->attributes()['nom'];
                $jour->ferme = (string)$jourUnique->attributes()['ferme'];

                $jour->horaire = [];
                foreach ($jourUnique as $jourHoraire) {
                    $jourHoraireUnique = new \stdClass();
                    $jourHoraireUnique->ouverture = (string)$jourHoraire->attributes()['ouverture'];
                    $jourHoraireUnique->fermeture = (string)$jourHoraire->attributes()['fermeture'];
                    array_push($jour->horaire, $jourHoraireUnique);
                }
                array_push($jours, $jour);
            }
            $horaires->jour = $jours;
            $item->horaires = $horaires;
        } else {
            $item->horaires = $horaires;
        }
        $item->service = [];
        foreach ($datas->services->service as $serviceUnique) {
            array_push($item->service, (string)$serviceUnique);
        }

        $item->prix = [];
        foreach ($datas->prix as $uniquePrice) {
            $itemPrice = new \stdClass();
            $itemPrice->nom = (string)$uniquePrice->attributes()['nom'];
            $itemPrice->maj = (string)$uniquePrice->attributes()['maj'];
            $itemPrice->valeur = (string)$uniquePrice->attributes()['valeur'];
            array_push($item->prix, $itemPrice);
        }
        return $item;
    }

    private function downloadDataFile() {
        $source = "https://donnees.roulez-eco.fr/opendata/instantane";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/zip']);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $destination = "./PrixCarburants_instantane.zip";
        $file = fopen($destination, "w+");
        fputs($file, $data);
        fclose($file);

        $zip = new \ZipArchive;
        if ($zip->open('./PrixCarburants_instantane.zip') === TRUE) {
            $zip->extractTo('data/essence/');
            $zip->close();

            if (file_exists('data/essence/PrixCarburants_instantane.xml')) {
                $xml = simplexml_load_file('data/essence/PrixCarburants_instantane.xml');

                return $xml;
            } else {
                exit('Echec lors de l\'ouverture du fichier PrixCarburants_instantane.xml.');
            }
        } else {
            exit('Echec lors de l\'extraction du fichier PrixCarburants_instantane.zip.');
        }
    }

    private function distance($lat1, $lon1, $lat2, $lon2, $unit) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

}