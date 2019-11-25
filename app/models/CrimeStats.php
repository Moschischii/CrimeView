<?php

class CrimeStats implements JsonSerializable
{
    private $rate;
    private $distribution;

    public function __construct()
    { }

    public static function withRate(float $rate, array $distribution): CrimeStats
    {
        $instance = new self();
        $instance->initWithRate($rate, $distribution);
        return $instance;
    }

    protected function initWithRate(float $rate, array $distribution)
    {
        $this->rate = $rate;
        $this->distribution = $distribution;
    }

    public function getRate()
    {
        return $this->rate;
    }

    public function jsonSerialize()
    {
        foreach ($this->distribution as $key => $value) {
            $hold[] = [$key => $value];
        }

        return [
            'rate' => $this->rate,
            'distribution' => $hold
        ];
    }
}
