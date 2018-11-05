<?php
namespace Air\Database;

interface Process
{
    public function fetch();
    public function fetchAll();
    public function execute();

    public function setModel(Model $mode) : Process;
    public function getModel() : Model;

    public function setQueryBuild(QueryBuild $queryBuild) : Process;
    public function getQueryBuild() : QueryBuild;
}