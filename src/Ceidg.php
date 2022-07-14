<?php

namespace Dominservice\CeidgDataWarehouse;

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
        'ids' => 'array',
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
    private $smallRequestTime;

    /**
     * @var int
     */
    private $bigRequestTime;

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

    private $lastResponse;

    private $getAll = false;

    private $companyList = [];

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
        if (!$this->firstRequestTime) {
            $this->firstRequestTime = time();
        }
        if (!$this->smallRequestTime) {
            $this->smallRequestTime = time();
        }
        if (!$this->bigRequestTime) {
            $this->bigRequestTime = time();
        }

        if ($this->smallRequestTime + strtotime("+{$this->limitSmallMinutes} minutes") < time()) {
            $this->smallRequestTime = time();
            $this->smallCountRequests = 0;
        } else {
            $this->smallCountRequests++;
        }

        if ($this->bigRequestTime + strtotime("+{$this->limitBigMinutes} minutes") < time()) {
            $this->bigRequestTime = time();
            $this->bigCountRequests = 0;
        } else {
            $this->bigCountRequests++;
        }

        if ($this->bigCountRequests > $this->limitBigCount || $this->smallCountRequests > $this->limitSmallCount) {
            return null;
        }

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
    public function setCriteria($criteria, $value = null)
    {
        if (is_array($criteria)) {
            foreach ($criteria as $criterion =>$val) {
                $this->setCriteriaValue($criterion, $val);
            }
        } elseif (!empty($value)) {
            $this->setCriteriaValue($criteria, $value);
        }

        return $this;
    }

    /**
     * @param $criteria
     * @param $value
     * @return void
     */
    private function setCriteriaValue($criteria, $value)
    {
        if (!empty($this->requestParametersAll[$criteria])) {
            if ($this->requestParametersAll[$criteria] === 'array') {
                $this->criteria[$criteria][] = $value;
            } elseif ($this->requestParametersAll[$criteria] === 'int') {
                $this->criteria[$criteria] = (int)$value;
            } elseif ($this->requestParametersAll[$criteria] === 'string') {
                $this->criteria[$criteria] = (string)$value;
            } elseif ($this->requestParametersAll[$criteria] === 'date'
                && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$value)
            ) {
                $this->criteria[$criteria] = (string)$value;
            }
        }
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
        $this->lastResponse = $this->curl($this->criteria);

        return $this;
    }

    public function getCompanies($getAll = false, $getFullCompanyData = false)
    {
        $this->getAll = $getAll;
        $this->getFullCompanyData = $getFullCompanyData;

        $this->parseCompanyList();

        return $this->companyList;
    }


    public function parseCompanyList()
    {
        if ($this->lastResponse) {
            if (!empty($this->lastResponse->firmy)) {
                $ids = [];

                foreach ($this->lastResponse->firmy as $item) {
                    $tmp = clone $item;
                    unset($tmp->id, $tmp->link);


//                    dd($item, $tmp);

                    if ($this->getFullCompanyData) {
                        $ids[] = $item->id;
                    }

                    $this->companyList[$item->id] = $tmp;
                }

                if ($this->getFullCompanyData && !empty($ids)) {
                    foreach (array_chunk($ids, 5) as $chunk) {
                        $data = $this->curl([
                            'method' => 'firma',
                            'ids' => $chunk
                        ]);

                        foreach ($data->firma as $item) {
                            $this->companyList[$item->id]->adresDzialalnosci = !empty($item->adresDzialalnosci) ? $item->adresDzialalnosci : null;
                            $this->companyList[$item->id]->adresKorespondencyjny = !empty($item->adresKorespondencyjny) ? $item->adresKorespondencyjny : null;
                            $this->companyList[$item->id]->wlasciciel = !empty($item->wlasciciel) ? $item->wlasciciel : null;
                            $this->companyList[$item->id]->obywatelstwa = !empty($item->obywatelstwa) ? $item->obywatelstwa : null;
                            $this->companyList[$item->id]->pkd = !empty($item->pkd) ? $item->pkd : null;
                            $this->companyList[$item->id]->pkdGlowny = !empty($item->pkdGlowny) ? $item->pkdGlowny : null;
                            $this->companyList[$item->id]->dataRozpoczecia = !empty($item->dataRozpoczecia) ? $item->dataRozpoczecia : null;
                            $this->companyList[$item->id]->status = !empty($item->status) ? $item->status : null;
                            $this->companyList[$item->id]->numerStatusu = !empty($item->numerStatusu) ? $item->numerStatusu : null;
                            $this->companyList[$item->id]->wspolnoscMajatkowa = !empty($item->wspolnoscMajatkowa) ? $item->wspolnoscMajatkowa : null;
                            $this->companyList[$item->id]->spolki = !empty($item->spolki) ? $item->spolki : null;
                            $this->companyList[$item->id]->dataZawieszenia = !empty($item->dataZawieszenia) ? $item->dataZawieszenia : null;
                            $this->companyList[$item->id]->dataZakonczenia = !empty($item->dataZakonczenia) ? $item->dataZakonczenia : null;
                            $this->companyList[$item->id]->dataWykreslenia = !empty($item->dataWykreslenia) ? $item->dataWykreslenia : null;
                            $this->companyList[$item->id]->dataWznowienia = !empty($item->dataWznowienia) ? $item->dataWznowienia : null;
                            $this->companyList[$item->id]->telefon = !empty($item->telefon) ? $item->telefon : null;
                            $this->companyList[$item->id]->email = !empty($item->email) ? $item->email : null;
                            $this->companyList[$item->id]->www = !empty($item->www) ? $item->www : null;
                            $this->companyList[$item->id]->adresDoreczenElektronicznych = !empty($item->adresDoreczenElektronicznych) ? $item->adresDoreczenElektronicznych : null;
                            $this->companyList[$item->id]->innaFormaKonaktu = !empty($item->innaFormaKonaktu) ? $item->innaFormaKonaktu : null;
                            $this->companyList[$item->id]->wspolnoscMajatkowaDataUstania = !empty($item->wspolnoscMajatkowaDataUstania) ? $item->wspolnoscMajatkowaDataUstania : null;
                            $this->companyList[$item->id]->dataZgonu = !empty($item->dataZgonu) ? $item->dataZgonu : null;
                            $this->companyList[$item->id]->zarzadSukcesyjnyDataUstanowienia = !empty($item->zarzadSukcesyjnyDataUstanowienia) ? $item->zarzadSukcesyjnyDataUstanowienia : null;
                            $this->companyList[$item->id]->zarzadSukcesyjnyDataWygasniecia = !empty($item->zarzadSukcesyjnyDataWygasniecia) ? $item->zarzadSukcesyjnyDataWygasniecia : null;
                            $this->companyList[$item->id]->podstawyPrawneWykreslenia = !empty($item->podstawyPrawneWykreslenia) ? $item->podstawyPrawneWykreslenia : null;
                            $this->companyList[$item->id]->zakazy = !empty($item->zakazy) ? $item->zakazy : null;
                            $this->companyList[$item->id]->upadlosc = !empty($item->upadlosc) ? $item->upadlosc : null;
                            $this->companyList[$item->id]->zarzadcaSukcesyjny = !empty($item->zarzadcaSukcesyjny) ? $item->zarzadcaSukcesyjny : null;
                            $this->companyList[$item->id]->kwalifikacjeZawodowe = !empty($item->kwalifikacjeZawodowe) ? $item->kwalifikacjeZawodowe : null;
                            $this->companyList[$item->id]->uprawnienia = !empty($item->uprawnienia) ? $item->uprawnienia : null;
                            $this->companyList[$item->id]->ograniczenia = !empty($item->ograniczenia) ? $item->ograniczenia : null;
                            $this->companyList[$item->id]->ograniczeniaZdolnosciPrawnej = !empty($item->ograniczeniaZdolnosciPrawnej) ? $item->ograniczeniaZdolnosciPrawnej : null;
                        }
                    }
                }
            }

            if ($this->getAll
                && !empty($this->lastResponse->links)
                && $this->lastResponse->links->self !== $this->lastResponse->links->last
            ) {
                $this->lastResponse = $this->curl([], $this->lastResponse->links->next);
                $this->parseCompanyList();
            }
        }
        dump($this->lastResponse);
    }

    public function setDateFrom($date)
    {
        $this->setCriteria('dataod', $date);
    }

    public function setDateTo($date)
    {
        $this->setCriteria('datado', $date);
    }

    public function setNIP($nip)
    {
        $this->setCriteria('nip', $nip);
    }

    public function setNIP_SC($nip)
    {
        $this->setCriteria('nip_sc', $nip);
    }

    public function setREGON($nip)
    {
        $this->setCriteria('regon', $nip);
    }

    public function setREGON_SC($nip)
    {
        $this->setCriteria('regon_sc', $nip);
    }

    public function setFirstname($firstname)
    {
        $this->setCriteria('imie', $firstname);
    }

    public function setLastname($lastname)
    {
        $this->setCriteria('nazwisko', $lastname);
    }

    public function setName($name)
    {
        $this->setCriteria('nazwa', $name);
    }

    public function setStreet($steet)
    {
        $this->setCriteria('ulica', $steet);
    }

    public function setBuilding($building)
    {
        $this->setCriteria('budynek', $building);
    }

    public function setFlat($flat)
    {
        $this->setCriteria('lokal', $flat);
    }

    public function setCity($city)
    {
        $this->setCriteria('miasto', $city);
    }

    public function setVoivodship($voivodship)
    {
        $this->setCriteria('wojewodztwo', $voivodship);
    }

    public function setDistrict($district)
    {
        $this->setCriteria('powiat', $district);
    }

    public function setCommune($commune)
    {
        $this->setCriteria('gmina', $commune);
    }

    public function setPostcode($postcode)
    {
        $this->setCriteria('kod', $postcode);
    }

    public function setPKD($pkd)
    {
        $this->setCriteria('pkd', $pkd);
    }

    public function setPage($page)
    {
        $this->setCriteria('page', $page);
    }

    public function setLimit($limit)
    {
        if ($limit <= 25) {
            $this->setCriteria('limit', $limit);
        }
    }

    public function setStatus($status)
    {
        $this->setCriteria('status', $status);
    }

    public function setStatusActive()
    {
        $this->setCriteria('status', 'AKTYWNY');
    }
}
