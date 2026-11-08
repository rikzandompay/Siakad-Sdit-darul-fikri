<?php

namespace App\Notifications;

use App\Models\Wali;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

/**
 * Dasar notifikasi aplikasi MyDarfik: disimpan sebagai riwayat (lonceng di aplikasi) dan,
 * bila Firebase dikonfigurasi, dikirim sebagai push ke semua perangkat penerima.
 * Payload `rute` adalah alamat go_router yang dibuka saat notifikasi diketuk.
 */
abstract class NotifikasiAplikasi extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    /** konseling | pengumuman | izin | tugas */
    abstract protected function jenis(): string;

    abstract protected function judul(object $notifiable): string;

    abstract protected function isi(object $notifiable): string;

    /** Rute aplikasi untuk penerima ini, mis. /wali/izin atau /guru/konseling/3. */
    abstract protected function rute(object $notifiable): string;

    /** Notifikasi dengan tag sama saling menimpa di bilah notifikasi HP (mis. satu per percakapan). */
    protected function tag(): ?string
    {
        return null;
    }

    public function via(object $notifiable): array
    {
        return static::pushAktif() ? ['database', FcmChannel::class] : ['database'];
    }

    public static function pushAktif(): bool
    {
        return filled(config('firebase.projects.'.config('firebase.default').'.credentials'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => $this->jenis(),
            'judul' => $this->judul($notifiable),
            'isi' => $this->isi($notifiable),
            'rute' => $this->rute($notifiable),
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $data = $this->toArray($notifiable);
        $android = ['priority' => 'high', 'notification' => array_filter(['channel_id' => 'mydarfik', 'tag' => $this->tag()])];

        return (new FcmMessage(notification: new FcmNotification(title: $data['judul'], body: $data['isi'])))
            ->data(array_map('strval', $data + ['id' => (string) $this->id]))
            ->custom(['android' => $android]);
    }

    protected static function awalan(object $notifiable): string
    {
        return $notifiable instanceof Wali ? '/wali' : '/guru';
    }

    protected static function ringkas(?string $teks, int $batas = 100): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $teks)), $batas);
    }
}
