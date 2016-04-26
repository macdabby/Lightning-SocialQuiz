<section>
    <div class="row">
        <div class="columns">
            <h1><?=$quiz->title;?></h1>
            <p><?=$quiz->getQuestion();?></p>
            <form action="/quiz" method="post">
                <?= \Lightning\Tools\Form::renderTokenInput(); ?>
                <?= $quiz->renderOptions(); ?>
                <input type="hidden" name="q" value="<?=$quiz->quiz_name;?>">
                <input type="hidden" name="page" value="<?= $quiz->getQuestionPosition(); ?>">
                <input type="submit" name="submit" value="Submit" class="button" />
            </form>
        </div>
    </div>
</section>
