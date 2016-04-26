<?php

namespace Modules\SocialQuiz\Pages;

use Lightning\Tools\Database;
use Lightning\Tools\Navigation;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\Page;
use Modules\SocialQuiz\Model\SocialQuiz;
use Overridable\Lightning\Tools\Request;
use stdClass;

class Quiz extends Page {

    protected $session;
    protected $quizData;
    protected $questions;

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
        if (empty($this->quiz)) {
            throw new \Exception('Invalid Quiz');
        }
        $this->quizData = !empty($this->session->content->quiz_data->{$this->quiz->quiz_name}) ? $this->session->content->quiz_data->{$this->quiz->quiz_name} : new stdClass();
        $this->quizData->answers = !empty($this->quizData->answers) ? (array) $this->quizData->answers : [];
        $this->quiz->setData($this->quizData);
    }

    public function get() {
        if (empty($this->quizData->page)) {
            $this->quizData->page = 0;
        }

        if ($this->quizData->page >= $this->quiz->getQuestionCount()) {
            // Save results.
            $this->quiz->saveResults();
            // Clear the results from the session.
            unset($this->session->content->quiz_data->{$this->quiz->quiz_name});
            $this->session->save();
            if ($this->quiz->final_page == 'results') {
                Navigation::redirect('/quiz/results', ['q' => $this->quiz->quiz_name]);
            } else if (!empty($this->quiz->final_page)) {
                Navigation::redirect($this->quiz->final_page);
            } else {
                $this->page = ['share', 'SocialQuiz'];
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
        $this->quizData->answers[$this->quiz->getQuestionID()] = $this->quiz->getAnswerValue($answer);
        $this->quizData->page = $page + 1;
        $this->quiz->saveData();
        $this->redirect(['q' => $this->quiz->quiz_name]);
    }
}
