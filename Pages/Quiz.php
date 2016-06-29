<?php

namespace Modules\SocialQuiz\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\PHP;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;
use Modules\SocialQuiz\Model\SocialQuiz;
use Overridable\Lightning\Tools\Request;
use stdClass;

class Quiz extends Page {

    protected $session;
    protected $quizData;
    protected $questions;

    protected $fullWidth = true;

    /**
     * @var SocialQuiz
     */
    protected $quiz;

    public function hasAccess() {
        return true;
    }

    public function __construct() {
        parent::__construct();
        $this->session = Session::getInstance();
        $this->quiz = SocialQuiz::loadByName(Request::get('q'));
        if (empty($this->quiz) && $default_id = Configuration::get('social_quiz.default_quiz')) {
            $this->quiz = SocialQuiz::loadByID($default_id);
        }
        if (empty($this->quiz)) {
            throw new \Exception('Invalid Quiz');
        }
        $this->quizData = !empty($this->session->content->quiz_data->{$this->quiz->quiz_name}) ? $this->session->content->quiz_data->{$this->quiz->quiz_name} : new stdClass();
        $this->quizData->answers = !empty($this->quizData->answers) ? PHP::ObjectToArray($this->quizData->answers) : [];
        $this->quiz->setData($this->quizData);
        $this->meta['title'] = $this->quiz->title;
    }

    public function get() {
        $p = Request::get('p', Request::TYPE_INT);
        if ($p !== null && !empty($this->quiz->preview_image)) {
            // This is a link from a previous test. Set the preview image to the score.
            if (!empty($this->quiz->preview_images)) {
                $images = json_decode($this->quiz->preview_images);
                $diff = INF;
                $best = 0;
                foreach ($images as $i) {
                    if (abs($i - $p) < $diff) {
                        $best = $i;
                        $diff = abs($best - $p);
                    }
                }
                $p = $best;
            }
            $this->meta['image'] = str_replace('%', $p, Configuration::get('web_root') . $this->quiz->preview_image);
        }

        if (empty($this->quizData->page)) {
            $this->quizData->page = 0;
        }

        if ($this->quizData->page >= $this->quiz->getQuestionCount()) {
            // Save results.
            $this->quiz->saveResults();
            // Clear the results from the session.
            unset($this->session->content->quiz_data->{$this->quiz->quiz_name});
            $this->session->save();
            if ($this->quiz->final_page == 'results' || empty($this->quiz->final_page)) {
                $params = ['q' => $this->quiz->quiz_name];
                if ($this->quiz->type == SocialQuiz::TYPE_TEST) {
                    $params['score'] = $this->quiz->getScore();
                }
                Navigation::redirect('/quiz/results', $params, false, true);
            } else if (!empty($this->quiz->final_page)) {
                Navigation::redirect($this->quiz->final_page);
            }
        } else {
            $this->page = ['quiz', 'SocialQuiz'];
            $this->quiz->setQuestionPosition($this->quizData->page);
        }
        Template::getInstance()->set('quiz', $this->quiz);
    }

    public function post() {
        $page = Request::post('page', 'int');
        $answer = Request::post('answer');
        $this->quiz->setQuestionPosition($page);

        // Check for a valid answer.
        if (!empty($this->quiz->show_answers)) {
            if ($this->quiz->getCorrectAnswerIndex() == $answer) {
                Messenger::message('Correct!');
            }
            else if ($this->quiz->show_answers == SocialQuiz::SHOW_CORRECT_ANSWER) {
                Messenger::error('Wrong! The correct answer is: ' . $this->quiz->getAnswer());
            }
            else {
                Messenger::error('Wrong!');
            }
        }

        $this->quizData->answers[$this->quiz->getQuestionID()] = $this->quiz->getAnswerValue($answer);
        $this->quizData->page = $page + 1;
        $this->quiz->saveData();
        $this->redirect(['q' => $this->quiz->quiz_name]);
    }
}
