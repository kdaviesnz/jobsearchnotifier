<?php

namespace kdaviesnz\jobsearchnotifier;


use GuzzleHttp\Client;

interface IJobSearchNotifier
{
    public function getFreelancerJobs():array;
    public function fetchFreelancerProjects(string $keyphrase, Client $client):array;
    public function sendMail(string $email);
    public function getUpworkJobs();
    public function getJobs();
}