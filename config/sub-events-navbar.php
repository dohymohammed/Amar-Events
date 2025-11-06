
<?php
$subPages = [
    'participants' => '/organizer/participants?id=' . ($eventId ?? ''),
    'edit'         => '/organizer/edit?id=' . ($eventId ?? ''),
    'configure'          => '/organizer/configure?id=' . ($eventId ?? ''),
    'fields'       => '/organizer/fields?id=' . ($eventId ?? ''),
    'socials'      => '/organizer/socials?id=' . ($eventId ?? ''),
    'notice'       => '/organizer/notice?id=' . ($eventId ?? ''),
    'email'       => '/organizer/email?id=' . ($eventId ?? ''),
    'sms'          => '/organizer/sms?id=' . ($eventId ?? ''),
    'Back'       => '/organizer/events?id=' . ($eventId ?? '')
];


$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function isSubActive($link, $currentPath) {
    $linkPath = parse_url($link, PHP_URL_PATH); 
    return $linkPath === $currentPath 
        ? 'bg-blue-600 text-white' 
        : 'bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white';
}
?>
<div class="w-full bg-[#1e1e2f] shadow-md md:ml-72">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex overflow-x-auto space-x-2 py-3">
            <?php foreach($subPages as $name => $link): ?>
                <a href="<?= $link ?>"
                   class="flex-shrink-0 px-4 py-2 rounded-lg font-semibold whitespace-nowrap <?= isSubActive($link, $currentPath) ?>">
                   <?= ucfirst($name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>