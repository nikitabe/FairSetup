<?php

function get_hs_color_palette()
{
	/* -- original colors 
	$colors = Array( 
			'#4572A7', 
			'#AA4643', 
			'#89A54E', 
			'#80699B', 
			'#3D96AE', 
			'#DB843D', 
			'#92A8CD', 
			'#A47D7C', 
			'#B5CA92' );
	*/

	// Happy
/*	$colors = Array( 
			'#FFA314', 
			'#FBEF37', 
			'#C2FB37', 
			'#37FBE4', 
			'#3784FB', 
			'#E737FB', 
			'#FB37A5', 
			'#FB3737', 
			'#37FB96',
			'#B0FB37' );*/

	// Two-piece
/*	$colors = Array( 
			'#FFA314', 
			'#FFB542', 
			'#FFBE59', 
			'#FFC872', 
			'#FFD189', 
			'#E8F3F8', 
			'#DBE6EC', 
			'#C2CBCE', 
			'#A4BCC2',
			'#81A8B8' );*/
// ligh-accent
/*	
	$colors = Array( 
			'#FEC86C', 
			'#FEEE6C', 
			'#A7FE6C', 
			'#6CE9FE', 
			'#6CB3FE', 
			'#F893FE', 
			'#FEAFB0', 
			'#FEF2B1', 
			'#37FB96',
			'#B0FB37' );
*/

/*
	// Accent and grayscale
	// Light Orange
	$colors = Array();
	for( $i=0; $i<10; $i++ )
	{
		$c = HSVtoRGB( Array( (abs(40 + $i*10) % 360)/360, 0.7, 1) );
		$colors[$i] = rgb2html( $c[0], $c[1], $c[2] );
	}
*/

/* 
	$colors = Array();
	for( $i=0; $i<20; $i++ )
	{
		$c = HSVtoRGB( Array( ((45 + $i*50) % 360)/360, 0.9, 1) );
		$colors[$i] = rgb2html( $c[0], $c[1], $c[2] );
	}

	*/
/*
	// Playing around
	$colors = Array( 
			'#FF9A2F', 
			'#699CBA', 
			'#86BB75', 
			'#9E55B9', 
			'#F65687', 
			'#E75CB7', 
			'#FEAFB0', 
			'#FEF2B1', 
			'#37FB96',
			'#B0FB37' );
*/

	$colors = Array();
	for( $i=0; $i<20; $i++ )
	{
		$j = $i < 10 ? $i : 10;
		$c = HSVtoRGB( Array( ((45 + $i*50) % 360)/360, 0, 0.35 + $j * 0.035) );
		$colors[$i] = rgb2html( $c[0], $c[1], $c[2] );
	}

	
	$palette = "colors: [";
	foreach( $colors as $color ){
		$palette .= "'$color',";
	}
	$palette = substr($palette ,0,-1);
	$palette .= "]";
	return $palette;
}

function rgb2html($r, $g=-1, $b=-1)
{
    if (is_array($r) && sizeof($r) == 3)
        list($r, $g, $b) = $r;

    $r = intval($r); $g = intval($g);
    $b = intval($b);

    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));

    $color = (strlen($r) < 2?'0':'').$r;
    $color .= (strlen($g) < 2?'0':'').$g;
    $color .= (strlen($b) < 2?'0':'').$b;
    return '#'.$color;
}
function HSVtoRGB(array $hsv) {
    list($H,$S,$V) = $hsv;
    //1
    $H *= 6;
    //2
    $I = floor($H);
    $F = $H - $I;
    //3
    $M = $V * (1 - $S);
    $N = $V * (1 - $S * $F);
    $K = $V * (1 - $S * (1 - $F));

    //4
	list($R,$G,$B) = array( 1, 1, 1 );
    switch ($I) {
        case 0:
            list($R,$G,$B) = array($V,$K,$M);
            break;
        case 1:
            list($R,$G,$B) = array($N,$V,$M);
            break;
        case 2:
            list($R,$G,$B) = array($M,$V,$K);
            break;
        case 3:
            list($R,$G,$B) = array($M,$N,$V);
            break;
        case 4:
            list($R,$G,$B) = array($K,$M,$V);
            break;
        case 5:
        case 6: //for when $H=1 is given
            list($R,$G,$B) = array($V,$M,$N);
            break;
    }
    return array($R * 255, $G * 255, $B * 255);
}
?>