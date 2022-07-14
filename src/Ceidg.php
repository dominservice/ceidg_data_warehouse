<?php

class Ceidg
{
    /**
     * Production URL.
     *
     * @var string
     */
    protected $productionUrl = 'https://dane.biznes.gov.pl/api/ceidg/v2';

    /**
     * Sandbox URL.
     *
     * @var string
     */
    protected $sandboxUrl = 'https://test-dane.biznes.gov.pl/api/ceidg/v2';
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $apiUrl;

    private $requestParametersAll = [
        'nip' => 'array',
        'regon' => 'array',
        'nip_sc' => 'array',
        'regon_sc' => 'array',
        'imie' => 'array',
        'nazwisko' => 'array',
        'nazwa' => 'array',
        'ulica' => 'array',
        'budynek' => 'array',
        'lokal' => 'array',
        'miasto' => 'array',
        'wojewodztwo' => 'array',
        'powiat' => 'array',
        'gmina' => 'array',
        'kod' => 'array',
        'pkd' => 'array',
        'page' => 'int',
        'limit' => 'int',
        'dataod' => 'date',
        'datado' => 'date',
        'status' => 'array',
    ];
    private $requestParametersSingle = [
        'nip' => 'int',
        'regon' => 'int',
    ];

    /**
     * @var array
     */
    private $criteria = [];

    /**
     * @var int
     */
    private $firstRequestTime;

    /**
     * @var int
     */
    private $limitSmallCount = 50;

    /**
     * @var int
     */
    private $limitSmallMinutes = 3;

    /**
     * @var int
     */
    private $smallCountRequests = 0;


    /**
     * @var int
     */
    private $limitBigCount = 1000;

    /**
     * @var int
     */
    private $limitBigMinutes = 60;

    /**
     * @var int
     */
    private $bigCountRequests = 0;

    /**
     * Class constructor.
     *
     * @param string $apiKey
     * @param bool   $sandbox
     */
    public function __construct($apiKey, $sandbox = false)
    {
        $this->apiUrl = !$sandbox ? $this->productionUrl : $this->sandboxUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * @param false|string $url
     * @param array $params
     * @return mixed
     */
    private function curl($params = [], $url = false)
    {
        if (!$url) {
            $url = $this->apiUrl;
        }

        if (!empty($params['method'])) {
            $url .= '/' . $params['method'];
            unset($params['method']);
        } else {
            $url .= '/firmy';
        }

        $query = !empty($params) ? http_build_query($params) : null;

        if ($query) {
            $url .= '?' . $query;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->apiKey
        ));
        $content = trim(curl_exec($ch));
        curl_close($ch);

        return json_decode($content);
    }

    /**
     * @param $criteria
     * @param $value
     * @return $this
     */
    public function serCriteria($criteria, $value = null)
    {
        if (is_array($criteria)) {
            array_merge_recursive($this->criteria, $criteria);
        } elseif (!empty($value)) {
            $this->criteria[$criteria][] = $value;
        }

        return $this;
    }

    /**
     * @param $key
     * @return array|mixed|null
     */
    public function getCriteria($key = null)
    {
        return $key ? (!empty($this->criteria[$key]) ? $this->criteria[$key] : $this->criteria) : null;
    }

    /**
     * @return $this
     */
    public function clearCriteria()
    {
        $this->criteria = [];
        return $this;
    }

    public function search()
    {
        $data = $this->curl($this->criteria);

        return $this;
    }

    private function parseCompanyList()
    {

    }
}