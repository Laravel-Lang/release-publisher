<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PullRequestData;
use App\Jobs\GitHub\AutoMergeJob;
use App\Jobs\GitHub\DependabotJob;
use GrahamCampbell\GitHub\GitHubManager;
use Illuminate\Support\Arr;

class PullRequest
{
    protected string $autoApproveMessage = 'Auto approve';

    public function __construct(
        protected GitHubManager $github,
        protected TeamParser $teamParser,
        protected User $user,
    ) {}

    public function autoMerge(PullRequestData $data): void
    {
        AutoMergeJob::dispatch($data);
    }

    public function approve(PullRequestData $data): void
    {
        $this->github->pullRequest()->reviews()->create(
            $data->organization,
            $data->repository,
            $data->id,
            ['event' => 'APPROVE', 'body' => $this->autoApproveMessage]
        );
    }

    public function wasApproved(PullRequestData $data): bool
    {
        $reviews = $this->github->pullRequest()->reviews()->all(
            $data->organization,
            $data->repository,
            $data->id
        );

        foreach ($reviews as $review) {
            $body   = Arr::get($review, 'body');
            $userId = Arr::get($review, 'user.id');

            if ($body === $this->autoApproveMessage && $this->user->isMe($userId)) {
                return true;
            }
        }

        return false;
    }

    public function merge(PullRequestData $data): void
    {
        $this->github->pullRequest()->merge(
            $data->organization,
            $data->repository,
            $data->id,
            $data->body,
            $data->hash
        );
    }

    public function assign(PullRequestData $data): void
    {
        if (! blank($users = $this->matesForRequest($data))) {
            $this->github->issues()->assignees()->add(
                $data->organization,
                $data->repository,
                $data->id,
                ['assignees' => $users->all()]
            );

            $this->github->pullRequest()->reviewRequests()->create(
                $data->organization,
                $data->repository,
                $data->id,
                $users->reject(fn (string $user) => $user === $data->author)->all()
            );
        }
    }

    public function comment(PullRequestData $data, string $body): void
    {
        $this->github->issues()->comments()->create(
            $data->organization,
            $data->repository,
            $data->id,
            compact('body')
        );
    }

    public function dependabot(PullRequestData $data): void
    {
        DependabotJob::dispatch($data);
    }

    protected function matesForRequest(PullRequestData $data)
    {
        return $this->teamParser->forLocale($data->title)->reject(
            fn (string $username) => $username === $data->author
        );
    }
}
