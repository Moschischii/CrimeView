<?php

/**
 * Represents a SampleDataProvider used to return sample data.
 * 
 * A SampleDataProvider is used to increase development speed since no external API calls are needed.
 */
class SampleDataProvider implements ICountyDataProvider, ICrimeDataProvider, ICityDataProvider
{
    public function fillCountiesWithCrimeStats(array &$counties, int $countDistribution = 3)
    {
        $data18 = file_get_contents(__DIR__ . "/../samples/bka/BKA-18.csv");
        $data17 = file_get_contents(__DIR__ . "/../samples/bka/BKA-17.csv");
        $data16 = file_get_contents(__DIR__ . "/../samples/bka/BKA-16.csv");

        $this->fillCountiesWithCrimeStatsData(2018, $data18, $counties, $countDistribution);
        $this->fillCountiesWithCrimeStatsData(2017, $data17, $counties, $countDistribution);
        $this->fillCountiesWithCrimeStatsData(2016, $data16, $counties, $countDistribution);
    }

    private function fillCountiesWithCrimeStatsData(int $year, string $data, array &$counties, int $countDistribution = 3)
    {
        $rows = explode("\n", $data);
        $dd = [];

        foreach ($rows as $row) {
            $rowAsArray = str_getcsv($row, ";");
            if (count($rowAsArray) != 18) continue;

            $crimeType = utf8_encode($rowAsArray[1]);

            if ($rowAsArray[0] == "------") {
                $dd[$rowAsArray[2]]["Straftaten insgesamt"] = $this->csvNumberToFloat($rowAsArray[6]);
            } else if (strpos($crimeType, "insgesamt") === false) {
                $dd[$rowAsArray[2]][$crimeType] = $this->csvNumberToFloat($rowAsArray[5]);
            }
        }

        foreach ($counties as $county) {
            $id = ltrim($county->getId(), '0');
            if (array_key_exists($id, $dd)) {
                arsort($dd[$id]);
                $crimeDistribution = array_slice($dd[$id], 1, $countDistribution);
                $county->setCrimeStats(new CrimeStats($year, $dd[$id]["Straftaten insgesamt"] / 100000, $crimeDistribution));
            } else {
                $county->setCrimeStats(new CrimeStats(0, 0, ['No crime distribution available' => 0]));
            }
        }
    }

    public function getCountiesOnRoute(City $from, City $to): array
    {
        $pathToGeoJson = __DIR__ . "/../samples/geojson/landkreise.geojson";
        $geoJson = file_get_contents($pathToGeoJson);
        $raw = json_decode($geoJson, true);
        $features = $raw["features"];
        $counties = array();
        foreach ($features as $feature) {
            $name = $feature["properties"]["name_2"];
            $type = $feature["properties"]["type_2"];
            $stateName = $feature["properties"]["name_1"];
            $id = $feature["properties"]["cca_2"];
            $geo = json_encode($feature);
            $counties[] = new County($id, $name, $type, $stateName, $geo);
        }
        return $counties;
    }

    public function getCityByName(string $name): City
    {
        switch (strtolower($name)) {
            case "regensburg":
                return new City("Regensburg, Oberpfalz, Bayern, 93047, Deutschland", "city", 49.0195333, 12.0974869);
            case "nuernberg":
                return new City("Nürnberg, Mittelfranken, Bayern, Deutschland", "city", 49.453872, 11.077298);
            case "erlangen":
                return new City("Erlangen, Mittelfranken, Bayern, 91052, Deutschland", "city", 49.5981187, 11.003645);
            default:
                return new City("Hamburg, 20095, Deutschland", "city", 53.550341, 10.000654);
        }
    }

    private function csvNumberToFloat(string $number): float
    {
        $number = str_replace(',', '', $number);
        return floatval($number);
    }
}
