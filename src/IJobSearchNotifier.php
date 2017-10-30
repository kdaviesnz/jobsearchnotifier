<?php

namespace kdaviesnz\jobsearchnotifier;


use GuzzleHttp\Client;

interface IJobSearchNotifier
{
    public function getJobs():array;
    public function fetch(string $keyphrase, Client $client):array;
    public function sendMail(string $email);
}