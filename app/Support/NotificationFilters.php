<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class NotificationFilters
{
    /**
     * Exclude Laravel DB notifications for shop/order chat (surfaced via Messages instead).
     *
     * @param  Builder|\Illuminate\Database\Eloquent\Relations\MorphMany  $query
     */
    public static function excludeNewChatMessages(Builder|Relation $query): Builder|Relation
    {
        $driver = $query->getConnection()->getDriverName();

        return match ($driver) {
            'mysql' => $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(`data`, "$.kind")) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(`data`, "$.kind")) <> ?)', ['new_chat_message']),
            'pgsql' => $query->whereRaw("(coalesce(data->>'kind', '') <> ?)", ['new_chat_message']),
            default => $query->whereRaw('(json_extract(`data`, \'$.kind\') IS NULL OR json_extract(`data`, \'$.kind\') <> ?)', ['new_chat_message']),
        };
    }
}
