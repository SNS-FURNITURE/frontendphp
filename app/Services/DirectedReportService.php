<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DirectedReportService
{
    public function __construct(private NotifyService $notify) {}

    /**
     * @param  list<int>  $parentReportIds
     * @return array<string, mixed>
     */
    public function insertDirectedReport(
        string $title,
        string $body,
        string $roleName,
        ?User $author,
        int $recipientId,
        string $recipientName,
        ?string $entityType = null,
        ?int $entityId = null,
        array $parentReportIds = [],
    ): array {
        $payload = [
            'report_type' => $roleName,
            'title' => $title,
            'body' => $body ?: null,
            'posted_by_user_id' => $author?->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'is_locked' => true,
        ];

        $table = (new Report)->getTable();

        if (Schema::hasColumn($table, 'role_name')) {
            $payload['role_name'] = $roleName;
        }
        if (Schema::hasColumn($table, 'author_id')) {
            $payload['author_id'] = $author?->id;
        }
        if (Schema::hasColumn($table, 'author_name')) {
            $payload['author_name'] = $author?->full_name ?: '';
        }
        if (Schema::hasColumn($table, 'sent_to_user_id')) {
            $payload['sent_to_user_id'] = $recipientId;
        }
        if (Schema::hasColumn($table, 'sent_to_name')) {
            $payload['sent_to_name'] = $recipientName;
        }
        if (Schema::hasColumn($table, 'immutable')) {
            $payload['immutable'] = true;
        }
        if (Schema::hasColumn($table, 'delivered_at')) {
            $payload['delivered_at'] = now();
        }

        if (! Schema::hasColumn($table, 'sent_to_user_id') && $body !== '') {
            $payload['body'] = "To: {$recipientName} (#{$recipientId})\n\n".$body;
        }

        $report = Report::query()->create($payload);

        if (Schema::hasTable('report_sources')) {
            foreach (array_unique(array_filter($parentReportIds)) as $parentId) {
                DB::table('report_sources')->insertOrIgnore([
                    'report_id' => $report->id,
                    'parent_report_id' => $parentId,
                ]);
            }
        }

        $this->notify->notifyUser($recipientId, [
            'type' => 'report_received',
            'title' => 'New report: '.$title,
            'message' => ($author?->full_name ?: 'A colleague').' sent you a report.',
            'entityType' => 'report',
            'entityId' => (int) $report->id,
        ]);

        return array_merge($report->fresh(['author'])->toApiArray(), [
            'parent_report_ids' => array_values(array_unique(array_filter($parentReportIds))),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function maybeGenerateTaskReport(User $actor, ProjectTask $task, ?int $recipientUserId = null): ?array
    {
        $recipientId = $recipientUserId ? (int) $recipientUserId : 0;
        $recipientName = '';

        if ($recipientId) {
            $recipient = User::query()->where('id', $recipientId)->where('is_active', true)->first();
            if (! $recipient) {
                return null;
            }
            $recipientName = (string) $recipient->full_name;
        } else {
            $manager = $this->notify->findActiveUserByRole('company_manager');
            if (! $manager) {
                return null;
            }
            $recipientId = $manager['id'];
            $recipientName = $manager['full_name'];
        }

        $task->loadMissing(['project', 'assignee']);
        $body = implode("\n", [
            'Task marked done by '.($actor->full_name ?: 'A colleague').'.',
            'Project: '.($task->project?->name ?: '#'.$task->project_id),
            'Task: '.$task->title,
            'Assignee: '.($task->assignee?->full_name ?: 'Unassigned'),
            'Due: '.($task->due_date ?: '—'),
            'Status: '.$task->status,
        ]);

        $roleName = $actor->roles->first()?->name ?: 'operations';

        return $this->insertDirectedReport(
            'Task complete: '.$task->title,
            $body,
            $roleName,
            $actor,
            $recipientId,
            $recipientName,
            'task',
            (int) $task->id,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadReportDetail(int $id): ?array
    {
        $report = Report::query()->with('author')->find($id);
        if (! $report) {
            return null;
        }

        $parents = [];
        if (Schema::hasTable('report_sources')) {
            $parents = DB::table('report_sources as s')
                ->join('reports as r', 'r.id', '=', 's.parent_report_id')
                ->where('s.report_id', $id)
                ->select('r.id', 'r.title', 'r.posted_at')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        }

        $sourceTask = null;
        if ($report->entity_type === 'task' && $report->entity_id) {
            $task = ProjectTask::query()->with('project')->find($report->entity_id);
            if ($task) {
                $sourceTask = [
                    'id' => (int) $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'due_date' => $task->due_date,
                    'project_id' => (int) $task->project_id,
                    'project_name' => $task->project?->name,
                ];
            }
        }

        return $report->toApiArray([
            'parent_reports' => $parents,
            'source_task' => $sourceTask,
        ]);
    }
}
