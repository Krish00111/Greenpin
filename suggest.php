<?php
// suggest.php
header('Content-Type: application/json');
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid input']);
    exit;
}

$lat = floatval($input['lat'] ?? 0);
$lng = floatval($input['lng'] ?? 0);
$soil = $input['soil'] ?? 'loamy';
$water = $input['water'] ?? 'medium';
$irrigation = $input['irrigation'] ?? 'no';

// --- NEW LOGIC ---
// We IGNORE the climate dropdown and calculate it from the map pin.
$climate = getClimateFromLatLng($lat, $lng);
// --- END NEW LOGIC ---


// Determine hemisphere from latitude
$hemisphere = ($lat < 0) ? 'southern' : 'northern';
$month = intval(date('n')); // server time month

// Get season based on location
$season = getSeason($month, $hemisphere);

// Small crop dataset - extendable
$crops = [
    ['name'=>'Rice','climate'=>['tropical'],'soil'=>['clay','silty','loamy'],'water'=>'high'],
    ['name'=>'Wheat','climate'=>['temperate','arid'],'soil'=>['loamy','clay'],'water'=>'medium'],
    ['name'=>'Maize','climate'=>['tropical','temperate'],'soil'=>['loamy','sandy'],'water'=>'medium'],
    ['name'=>'Millet','climate'=>['arid','tropical'],'soil'=>['sandy','loamy'],'water'=>'low'],
    ['name'=>'Sugarcane','climate'=>['tropical'],'soil'=>['loamy','silty'],'water'=>'high'],
    ['name'=>'Cotton','climate'=>['arid','tropical'],'soil'=>['loamy','sandy'],'water'=>'medium'],
    ['name'=>'Potato','climate'=>['temperate'],'soil'=>['loamy','sandy','silty'],'water'=>'medium'],
    ['name'=>'Tomato','climate'=>['temperate','tropical'],'soil'=>['loamy','sandy'],'water'=>'medium'],
    ['name'=>'Lentil','climate'=>['temperate','arid'],'soil'=>['loamy','silty'],'water'=>'low'],
    ['name'=>'Chickpea','climate'=>['arid','temperate'],'soil'=>['sandy','loamy'],'water'=>'low'],
    ['name'=>'Date Palm','climate'=>['arid'],'soil'=>['sandy'],'water'=>'low'],
    ['name'=>'Mustard','climate'=>['temperate'],'soil'=>['loamy'],'water'=>'low'],
    ['name'=>'Gram','climate'=>['arid'],'soil'=>['loamy'],'water'=>'low'],
    ['name'=>'Coconut','climate'=>['tropical'],'soil'=>['loamy','sandy'],'water'=>'high'],
    ['name'=>'Coffee','climate'=>['tropical'],'soil'=>['loamy','silty'],'water'=>'medium']
];

$suggestions = [];
$waterPref = ($irrigation === 'yes') ? 'high' : $water;

foreach($crops as $crop){
    $score = 0;
    
    // 1. Climate match (This is now smart)
    if(in_array($climate, $crop['climate'])) $score += 3;
    
    // 2. Soil match
    if(in_array($soil, $crop['soil'])) $score += 2;
    
    // 3. Water match (checks irrigation)
    if($crop['water'] === $waterPref) $score += 2;
    elseif($crop['water'] === 'medium' && $waterPref === 'high') $score += 1;
    elseif($crop['water'] === 'low' && $waterPref !== 'low') $score += 1;
    
    // 4. Season match
    if(isCropInSeason($crop['name'], $season)) $score += 3;
    
    if($score > 3){ // Only add if it's a decent match
        $suggestions[] = [
            'name'=>$crop['name'],
            'score'=>$score,
            'reason'=>"Matches $season, $climate climate, $soil soil and $waterPref water."
        ];
    }
}

usort($suggestions, function($a,$b){ return $b['score'] - $a['score']; });
$top = array_slice($suggestions, 0, 5); 


echo json_encode([
    'lat'=>$lat, 'lng'=>$lng,
    'season'=>$season,
    'climate_detected' => $climate, 
    'crops'=>$top
], JSON_PRETTY_PRINT);



function getClimateFromLatLng($lat, $lng) {
    if ($lat == 0 && $lng == 0) return 'temperate';


    if ($lat > 28) {
        return 'temperate';
    }
    if ($lat > 20 && $lat <= 28 && $lng < 79) {
        return 'arid';
    }

    if ($lat < 20) {
        return 'tropical';
    }
    

    return 'tropical'; 
}


function getSeason($month, $hemisphere='northern'){
    // Simplified seasons by month
    $north = [
        12=>'Winter',1=>'Winter',2=>'Winter',
        3=>'Spring',4=>'Spring',5=>'Spring',
        6=>'Summer',7=>'Summer',8=>'Summer',
        9=>'Autumn',10=>'Autumn',11=>'Autumn'
    ];
    $south = [
        12=>'Summer',1=>'Summer',2=>'Summer',
        3=>'Autumn',4=>'Autumn',5=>'Autumn',
        6=>'Winter',7=>'Winter',8=>'Winter',
        9=>'Spring',10=>'Spring',11=>'Spring'
    ];
    return ($hemisphere === 'southern') ? $south[$month] : $north[$month];
}

// rough season table for certain crops
function isCropInSeason($crop, $season){
    $map = [
        'Rice' => ['Summer','Autumn'], // Kharif
        'Wheat' => ['Winter','Spring'], // Rabi
        'Maize' => ['Summer','Autumn'], // Kharif
        'Millet' => ['Summer','Autumn'], // Kharif
        'Sugarcane' => ['Summer','Autumn','Winter','Spring'], // All year
        'Cotton' => ['Summer','Autumn'], // Kharif
        'Potato' => ['Winter','Spring'], // Rabi
        'Tomato' => ['Summer', 'Winter'],
        'Lentil' => ['Winter','Spring'], // Rabi
        'Chickpea' => ['Winter','Spring'], // Rabi
        'Date Palm' => ['Summer'],
        'Mustard' => ['Winter','Spring'], // Rabi
        'Gram' => ['Winter','Spring'], // Rabi
        'Coconut' => ['Summer','Autumn','Winter','Spring'], // All year
        'Coffee' => ['Summer','Autumn'] // Kharif
    ];
    if(!isset($map[$crop])) return true; // Default to true if not in map
    return in_array($season, $map[$crop]);
}