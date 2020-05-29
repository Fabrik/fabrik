<?php
/**
 * PolylineEncoder based on Mark McClure's Javascript PolylineEncoder
 * and Jim Hribar's PHP version. All nicely melted into a proper PHP5 class.
 *
 * @package     Google Maps Helpers
 * @since       2008-12-02
 * @author      Matthias Bauer <matthias@pulpmedia.at>
 * @see 		    http://facstaff.unca.edu/mcmcclur/GoogleMaps/EncodePolyline/
 * @copyright	  Original file: 2008, Pulpmedia Medientechnik und -design GmbH
 *              Enhancements and fixes:  Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 */

 /**
 * $$$ hugh - hacked around for Fabrik use, to allow using the 'radius widget' in static maps.
 * Fixed a bunch of undefined notices, and added the GMapCircle() method.
 */
class PolylineEncoder
{
	private $numLevels = 18;
	private $zoomFactor = 2;
	private $verySmall = 0.00001;
	private $forceEndpoints = true;
	private $zoomLevelBreaks = array();

	/**
	 * All parameters are set with useful defaults.
	 * If you actually want to understand them, see Mark McClure's detailed description.
	 *
	 * @see	http://facstaff.unca.edu/mcmcclur/GoogleMaps/EncodePolyline/algorithm.html
	 */
	public function __construct($numLevels = 18, $zoomFactor = 2, $verySmall = 0.00001, $forceEndpoints = true)
	{
		$this->numLevels = $numLevels;
		$this->zoomFactor = $zoomFactor;
		$this->verySmall = $verySmall;
		$this->forceEndpoints = $forceEndpoints;

		for($i = 0; $i < $this->numLevels; $i++)
		{
			$this->zoomLevelBreaks[$i] = $this->verySmall * pow($this->zoomFactor, $this->numLevels-$i-1);
		}
	}

	/**
	 * Generates all values needed for the encoded Google Maps GPolyline.
	 *
	 * @param array		Multidimensional input array in the form of
	 * 					array(array(latitude, longitude), array(latitude, longitude),...)
	 *
	 * @return stdClass	Simple object containing three public parameter:
	 * 					- points: the points string with escaped backslashes
   *          - levels: the encoded levels ready to use
   *          - rawPoints: the points right out of the encoder
   *          - numLevels: should be used for creating the polyline
   *          - zoomFactor: should be used for creating the polyline
	 */
	public function encode($points)
	{
		$dists = array();
    if(count($points) > 2)
    {
      $stack[] = array(0, count($points)-1);
      while(count($stack) > 0)
      {
          $current = array_pop($stack);
          $maxDist = $absMaxDist = 0;
          for($i = $current[0]+1; $i < $current[1]; $i++)
          {
            $temp = $this->distance($points[$i], $points[$current[0]], $points[$current[1]]);
        if($temp > $maxDist)
        {
            $maxDist = $temp;
            $maxLoc = $i;
            if($maxDist > $absMaxDist)
            {
                $absMaxDist = $maxDist;
            }
        }
          }
          if($maxDist > $this->verySmall)
          {
            $dists[$maxLoc] = $maxDist;
            array_push($stack, array($current[0], $maxLoc));
            array_push($stack, array($maxLoc, $current[1]));
          }
      }
    }

    $polyline = new stdClass();
    $polyline->rawPoints = $this->createEncodings($points, $dists);
    $polyline->levels = $this->encodeLevels($points, $dists, $absMaxDist);
    $polyline->points = str_replace("\\","\\\\", $polyline->rawPoints);
    $polyline->numLevels = $this->numLevels;
    $polyline->zoomFactor = $this->zoomFactor;

    return $polyline;
	}

	private function computeLevel($dd)
	{
	    if($dd > $this->verySmall)
	    {
	        $lev = 0;
	        while($dd < $this->zoomLevelBreaks[$lev])
		{
		    $lev++;
		}
	    }
	    return $lev;
	}

	private function distance($p0, $p1, $p2)
	{
	    if($p1[0] == $p2[0] && $p1[1] == $p2[1])
	    {
	        $out = sqrt(pow($p2[0]-$p0[0],2) + pow($p2[1]-$p0[1],2));
	    }
	    else
	    {
	        $u = (($p0[0]-$p1[0])*($p2[0]-$p1[0]) + ($p0[1]-$p1[1]) * ($p2[1]-$p1[1])) / (pow($p2[0]-$p1[0],2) + pow($p2[1]-$p1[1],2));
	        if($u <= 0)
	        {
	            $out = sqrt(pow($p0[0] - $p1[0],2) + pow($p0[1] - $p1[1],2));
	        }
	        if($u >= 1)
		{
	            $out = sqrt(pow($p0[0] - $p2[0],2) + pow($p0[1] - $p2[1],2));
	        }
	        if(0 < $u && $u < 1)
		{
	            $out = sqrt(pow($p0[0]-$p1[0]-$u*($p2[0]-$p1[0]),2) + pow($p0[1]-$p1[1]-$u*($p2[1]-$p1[1]),2));
	        }
	    }
	    return $out;
	}

	private function encodeSignedNumber($num)
	{
	   $sgn_num = $num << 1;
	   if ($num < 0)
	   {
	       $sgn_num = ~($sgn_num);
	   }
	   return $this->encodeNumber($sgn_num);
	}

	private function createEncodings($points, $dists)
	{
		$plat = $plng = 0;
		$encoded_points = '';
	    for($i=0; $i<count($points); $i++)
	    {
	        if(isset($dists[$i]) || $i == 0 || $i == count($points)-1)
		{
		    $point = $points[$i];
		    $lat = $point[0];
		    $lng = $point[1];
		    $late5 = floor($lat * 1e5);
		    $lnge5 = floor($lng * 1e5);
		    $dlat = $late5 - $plat;
		    $dlng = $lnge5 - $plng;
		    $plat = $late5;
		    $plng = $lnge5;
		    $encoded_points .= $this->encodeSignedNumber($dlat) . $this->encodeSignedNumber($dlng);
		}
	    }
	    return $encoded_points;
	}

	private function encodeLevels($points, $dists, $absMaxDist)
	{
		$encoded_levels = '';
	    if($this->forceEndpoints)
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels-1);
	    }
	    else
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($absMaxDist)-1);
	    }
	    for($i=1; $i<count($points)-1; $i++)
	    {
	        if(isset($dists[$i]))
		{
		    $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($dists[$i])-1);
		}
	    }
	    if($this->forceEndpoints)
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels -1);
	    }
	    else
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($absMaxDist)-1);
	    }
	    return $encoded_levels;
	}

	private function encodeNumber($num)
	{
		$encodeString = '';
	    while($num >= 0x20)
	    {
	        $nextValue = (0x20 | ($num & 0x1f)) + 63;
	        $encodeString .= chr($nextValue);
		$num >>= 5;
	    }
	    $finalValue = $num + 63;
	    $encodeString .= chr($finalValue);
	    return $encodeString;
	}

	/**
	 * Code to build a polyline circle, stolen from:
	 * http://stackoverflow.com/questions/7316963/drawing-a-circle-google-static-maps
	 * Needed for drawing radius circle on static maps
	 *
	 * @param unknown_type $Lat
	 * @param unknown_type $Lng
	 * @param unknown_type $Rad
	 * @param unknown_type $Detail
	 */
	public function GMapCircle($Lat,$Lng,$Rad,$Detail=8){
		$R    = 6371;

		$pi   = pi();

		$Lat  = ($Lat * $pi) / 180;
		$Lng  = ($Lng * $pi) / 180;
		$d    = $Rad / $R;

		$points = array();
		$i = 0;

		for ($i = 0; $i <= 360; $i+=$Detail)
		{
			$brng = $i * $pi / 180;

			$pLat = asin(sin($Lat)*cos($d) + cos($Lat)*sin($d)*cos($brng));
			$pLng = (($Lng + atan2(sin($brng)*sin($d)*cos($Lat), cos($d)-sin($Lat)*sin($pLat))) * 180) / $pi;
			$pLat = ($pLat * 180) /$pi;

			$points[] = array($pLat,$pLng);
		}

		$EncString = $this->encode($points);

		return $EncString->points;
	}
}
?>