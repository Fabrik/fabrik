<?php
$html[] = '<div class="sideBoxContent">';
$html[] = '<a href="http://www.kona-coffee-council.com/" target="_blank">';
$html[] = '<img ';
$html[] = 'src="images/kona_coffee_council_logo.gif" ';
$html[] = 'class="centered" ';
$html[] = 'border="3" ';
$html[] = 'width="84" ';
$html[] = 'height="100" ';
$html[] = 'alt="Click to visit the Kona Coffee Council website" ';
$html[] = 'title="Click to visit the Kona Coffee Council website" ';
$html[] = '/>';
$html[] = '</a>';
$html[] = '<hr />';
$html[] = '<a href="http://www.konacoffeefarmers.com/" target="_blank">';
$html[] = '<img ';
$html[] = 'src="images/kona_coffee_farmers_association_logo.jpg" ';
$html[] = 'class="centered" ';
$html[] = 'border="3" ';
$html[] = 'height="100" ';
$html[] = 'alt="Click to visit the Kona Coffee Farmers Association website" ';
$html[] = 'title="Click to visit the Kona Coffee Farmers Association website" ';
$html[] = '/>';
$html[] = '</a>';
$html[] = '</div>';
$content = implode("\n", $html) . "\n";
unset($html);