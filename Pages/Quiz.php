<?php

namespace Modules\SocialQuiz\Pages;

use Lightning\Tools\Session;
use Lightning\View\Page;
use Overridable\Lightning\Tools\Request;
use stdClass;

class Quiz extends Page {

    protected $session;
    protected $quizData

    public function __construct() {
        parent::__construct();
        $this->session = Session::getInstance();
        $this->quizData = !empty($this->session->content->quiz_data) ? $this->session->content->quiz_data : new stdClass();
        $this->quizData->answers = (array) $this->quizData->answers;
    }

    public function get() {
        if (empty($this->quizData->page)) {
            $this->quizData->page = 1;
            $this->quizData->complete = false;
        }

        if ($this->quizData->complete) {
            $this->page = 'share';
        } else {
            $this->page = 'quiz';
        }
    }

    public function post() {
        $page = Request::post('page', 'int');
        $answer = Request::post('answer', 'int');
        $this->quizData->answers[$page] = $answer;
    }
}
