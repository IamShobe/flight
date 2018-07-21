<?php namespace Flight\Views;
class StaticFile extends View {
    public function __invoke($vars) {
        return $this->static_file($vars["path"]);
    }
}
