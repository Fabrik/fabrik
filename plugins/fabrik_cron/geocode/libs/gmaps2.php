<?php

/**
 *
 * Borrowed from ...
 * http://www.phpclasses.org/browse/file/38470.html
 *
 */

Class GeoCode
{
    private $address = "";
    private $url = "";
	private $apiKey = "";
	private $verifyPeer = true;

	public function __construct($verifyPeer = true)
	{
		$this->verifyPeer = $verifyPeer;
	}

    public function getLatLng($addr, $returnType="array", $apiKey = "")
    {
        $this->address = $addr;
	    $this->apiKey  = $apiKey;

        $this-> makeUrl();

        $final = $this->parseGeoData();

        if($returnType == "json")
        {
            return $this->makeJson($final);
        }
        else
        {
            return $final;
        }
    }

	public function getAddress($lat, $lng, $returnType="array", $apiKey = "")
	{
		$this->lat = $lat;
		$this->lng = $lng;
		$this->apiKey  = $apiKey;

		$this->makeUrl('latlng');

		$final = $this->parseGeoData();

		if($returnType == "json")
		{
			return $this->makeJson($final);
		}
		else
		{
			return $final;
		}
	}


    private function makeJson($data)
    {
        return json_encode($data);
    }

    private function makeUrl($which = 'address')
    {
    	switch ($which)
		{
			case 'address':
				$this->address = str_replace(" ", "+", $this->address);
				$this->url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$this->address;
				break;
			case 'latlng':
				$this->url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$this->lat.",".$this->lng;
				break;
		}
        if (!empty($this->apiKey)) {
        	$this->url .= "&key=" . $this->apiKey;
        }
    }

    private function parseGeoData()
    {
	    $resultFromGl = array();

    	if (!$this->verifyPeer)
	    {
		    $arrContextOptions = array(
			    "ssl" => array(
				    "verify_peer"      => false,
				    "verify_peer_name" => false,
			    ),
		    );

		    $data = file_get_contents($this->url, false, stream_context_create($arrContextOptions));
	    }
	    else
	    {
		    $data = file_get_contents($this->url);
	    }

        $result = json_decode($data);

    	if (empty($result))
	    {
		    $resultFromGl['status'] = "Cannot connect to Google, probably SSL issue.";
	    }
	    else
	    {
		    if ($result->status == "OK")
		    {
			    if ($result->results[0]->geometry->location)
			    {
				    $addressFromGoogle = $result->results[0]->formatted_address;
				    $lat               = $result->results[0]->geometry->location->lat;
				    $lng               = $result->results[0]->geometry->location->lng;

				    $resultFromGl['status']  = $result->status;
				    $resultFromGl['address'] = $addressFromGoogle;
				    $resultFromGl['components'] = $addressFromGoogle = $result->results[0]->address_components;
				    $resultFromGl['lat']     = $lat;
				    $resultFromGl['lng']     = $lng;
			    }
			    else
			    {
				    $resultFromGl['status'] = "Address not found";
			    }
		    }
		    else
		    {
			    $resultFromGl['status'] = $result->status;
		    }
	    }

        return $resultFromGl;
    }

}

?>