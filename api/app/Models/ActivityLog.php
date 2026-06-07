<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const REGISTERED = 'auth.registered';

    public const LOGIN = 'auth.login';

    public const LOGOUT = 'auth.logout';

    public const PROVIDER_LINKED = 'auth.provider.linked';

    public const PROVIDER_UNLINKED = 'auth.provider.unlinked';

    public const FOLDER_CREATED = 'folder.created';

    public const FOLDER_DELETED = 'folder.deleted';

    public const FOLDER_VISIBILITY = 'folder.visibility_changed';

    public const SIGNS_UPLOADED = 'signs.uploaded';

    public const SIGNS_DELETED = 'signs.deleted';

    public const ADMIN_USER_BANNED = 'admin.user.banned';

    public const ADMIN_USER_UNBANNED = 'admin.user.unbanned';

    public const ADMIN_FOLDER_DELETED = 'admin.folder.deleted';

    public const ADMIN_SIGN_DELETED = 'admin.sign.deleted';

    protected $fillable = [
        'event',
        'actor_id',
        'subject_user_id',
        'subject_folder_id',
        'subject_sign_id',
        'metadata',
        'ip_address',
        'upload_session_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }
}
