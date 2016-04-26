<?php

namespace Modules\SocialQuiz\Pages;

use Lightning\Tools\Request;
use Modules\SocialQuiz\Model\SocialQuiz;
use Source\View\Page;

class AggregatedResults extends Page {

    protected $quiz;

    public function hasAccess() {
        return true;
    }

    public function get() {
        $this->quiz = SocialQuiz::loadByName(Request::get('q'));
        if (empty($this->quiz)) {
            throw new \Exception('Invalid Quiz');
        }

        $this->quiz->updateResults();
    }
}
