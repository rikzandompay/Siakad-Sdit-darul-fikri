package id.sch.darulfikri.mydarfik

import android.content.ContentValues
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import androidx.core.content.FileProvider
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel
import java.io.File

/**
 * Kanal "mydarfik/unduh": simpan laporan ke folder Download publik HP dan bukakan berkasnya.
 * Android 10+ memakai MediaStore (tanpa izin tambahan); Android 7-9 menulis langsung ke Download.
 */
class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "mydarfik/unduh").setMethodCallHandler { call, result ->
            try {
                when (call.method) {
                    "simpan" -> result.success(
                        simpan(call.argument<String>("nama")!!, call.argument<String>("mime")!!, call.argument<ByteArray>("data")!!)
                    )
                    "buka" -> {
                        val intent = Intent(Intent.ACTION_VIEW).apply {
                            setDataAndType(Uri.parse(call.argument<String>("uri")!!), call.argument<String>("mime"))
                            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
                        }
                        startActivity(Intent.createChooser(intent, "Buka dengan"))
                        result.success(true)
                    }
                    else -> result.notImplemented()
                }
            } catch (e: Exception) {
                result.error("gagal", e.message, null)
            }
        }
    }

    private fun simpan(nama: String, mime: String, data: ByteArray): String {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val values = ContentValues().apply {
                put(MediaStore.Downloads.DISPLAY_NAME, nama)
                put(MediaStore.Downloads.MIME_TYPE, mime)
                put(MediaStore.Downloads.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS)
                put(MediaStore.Downloads.IS_PENDING, 1)
            }
            val uri = contentResolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, values)
                ?: throw IllegalStateException("Folder Download tidak dapat diakses")
            contentResolver.openOutputStream(uri)!!.use { it.write(data) }
            values.clear()
            values.put(MediaStore.Downloads.IS_PENDING, 0)
            contentResolver.update(uri, values, null, null)
            return uri.toString()
        }
        @Suppress("DEPRECATION")
        val dir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
        dir.mkdirs()
        val file = File(dir, nama)
        file.writeBytes(data)
        return FileProvider.getUriForFile(this, "$packageName.unduh", file).toString()
    }
}
