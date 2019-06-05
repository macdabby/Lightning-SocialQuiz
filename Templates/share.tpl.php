<h1>You scored <?=$score;?>%!</h1>
<p>Challenge you friends by sharing the quiz!</p>
<?= \Lightning\View\SocialMedia\Links::render(\Lightning\Tools\Configuration::get('web_root') . '/quiz?q=' . $quiz->name . '&p=' . $score); ?>
