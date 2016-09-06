<section class="quiz">
    <div class="row">
        <div class="columns">
            <h1><?=$quiz->title;?></h1>
            <p><?=$quiz->getQuestion();?></p>
            <form action="/quiz" method="post" class="socialQuiz" data-abide>
                <div data-abide-error class="alert callout" style="display: none;">
                    <p><i class="fi-alert"></i> You have to answer the question before you can continue.</p>
                </div>
                <?= \Lightning\Tools\Form::renderTokenInput(); ?>
                <?= $quiz->renderOptions(); ?>
                <input type="hidden" name="q" value="<?=$quiz->quiz_name;?>">
                <input type="hidden" name="page" value="<?= $quiz->getQuestionPosition(); ?>">
                <button type="submit" name="submit" class="button right">Next&nbsp;&nbsp;<i class="fa fa-arrow-right"></i></button>
            </form>
        </div>
    </div>
</section>
