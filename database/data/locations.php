<?php

/*
|--------------------------------------------------------------------------
| Bangladesh administrative locations
|--------------------------------------------------------------------------
| Division => District => [Upazila / Thana, ...]
|
| Seed source for the `locations` table (see LocationSeeder).
| All 8 divisions and 64 districts are listed. Upazila / thana lists are filled
| in for the main districts; the remaining districts currently fall back to a
| single "<District> Sadar" entry. Add rows here and re-run the seeder to extend.
*/

$sadar = fn (string $district) => ["{$district} Sadar"];

$divisions = [
    'Barishal' => ['Barguna', 'Barishal', 'Bhola', 'Jhalokati', 'Patuakhali', 'Pirojpur'],
    'Chattogram' => ['Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', "Cox's Bazar", 'Cumilla', 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati'],
    'Dhaka' => ['Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail'],
    'Khulna' => ['Bagerhat', 'Chuadanga', 'Jashore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura', 'Meherpur', 'Narail', 'Satkhira'],
    'Mymensingh' => ['Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'],
    'Rajshahi' => ['Bogura', 'Chapainawabganj', 'Joypurhat', 'Naogaon', 'Natore', 'Pabna', 'Rajshahi', 'Sirajganj'],
    'Rangpur' => ['Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon'],
    'Sylhet' => ['Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet'],
];

$detailed = [
    'Dhaka' => ['Dhanmondi', 'Gulshan', 'Mirpur', 'Mohammadpur', 'Motijheel', 'Uttara', 'Savar', 'Keraniganj', 'Dhamrai'],
    'Gazipur' => ['Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Sreepur'],
    'Chattogram' => ['Kotwali', 'Panchlaish', 'Double Mooring', 'Hathazari', 'Patiya', 'Sitakunda'],
    'Cumilla' => ['Cumilla Sadar', 'Chandina', 'Daudkandi', 'Laksam'],
    'Sylhet' => ['Sylhet Sadar', 'Beanibazar', 'Golapganj', 'Companiganj'],
    'Rajshahi' => ['Boalia', 'Rajpara', 'Paba', 'Godagari'],
    'Khulna' => ['Khulna Sadar', 'Sonadanga', 'Dumuria', 'Batiaghata'],
    'Mymensingh' => ['Mymensingh Sadar', 'Muktagacha', 'Trishal', 'Bhaluka'],
    'Rangpur' => ['Rangpur Sadar', 'Mithapukur', 'Pirganj', 'Badarganj'],
    'Barishal' => ['Barishal Sadar', 'Bakerganj', 'Gaurnadi', 'Banaripara'],
];

$locations = [];
foreach ($divisions as $division => $districts) {
    foreach ($districts as $district) {
        $locations[$division][$district] = $detailed[$district] ?? $sadar($district);
    }
}

return $locations;
