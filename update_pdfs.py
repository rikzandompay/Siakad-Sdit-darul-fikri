import os
import re
import glob

directory = '/home/rikzan24_/SisforSinta/resources/views/exports'
files = glob.glob(os.path.join(directory, '*pdf.blade.php'))

replacement = """    <div class="kop-surat">
        <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
            <tr>
                <td style="width: 15%; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ asset('Lgo.png') }}" alt="Logo" style="width: 80px;">
                </td>
                <td style="width: 70%; text-align: center; border: none; padding: 0;">
                    <h1 style="font-size: 18pt; margin: 0; text-transform: uppercase; font-weight: bold;">SD IT DARUL FIKRI</h1>
                    <h2 style="font-size: 14pt; margin: 5px 0 0;">\\g<yayasan></h2>
                    <p style="font-size: 8pt; margin: 4px 0 0;">Alamat: Jl. Sungai Durian Laut Kec.Sungai Raya Kabupaten Kubu Raya, Provinsi Kalimantan Barat, Kode Pos 78391<br>Telp: (021) 1234567 | Email: info@sditdarulfikri.sch.id</p>
                </td>
                <td style="width: 15%; border: none; padding: 0;"></td>
            </tr>
        </table>
    </div>"""

# Match `<div class="kop-surat"> ... <h2>...</h2> ... </div>`
pattern = re.compile(r'<div class="kop-surat">.*?<h2>(?P<yayasan>.*?)</h2>.*?</div>', re.DOTALL)

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()
    
    new_content = pattern.sub(replacement, content)
    
    with open(filepath, 'w') as f:
        f.write(new_content)
    print(f"Updated {filepath}")
