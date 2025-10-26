# Bilet Satın Alma Platformu

Dinamik, veritabanı destekli ve çok kullanıcı rolleri olan bir otobüs bilet satın alma platformu.

## ✨ Temel Özellikler
Kullanıcı Rolleri: Ziyaretçi, Yolcu, Firma Admin, Admin gibi farklı yetki seviyeleri.

Sefer İşlemleri: Detaylı sefer arama ve listeleme.

Bilet Yönetimi: Bilet satın alma ve iptal etme fonksiyonları.

PDF Bilet: Satın alınan biletler için PDF formatında çıktı üretimi.

Yönetim Panelleri: Firma ve Admin kullanıcıları için CRUD (Oluşturma, Okuma, Güncelleme, Silme) işlemlerini içeren yönetim panelleri.

Kupon Yönetimi: İndirim kuponu oluşturma ve kullanma altyapısı.

Paketleme: Kolay dağıtım ve çalıştırma için Docker ile paketlenmiştir.

## Teknolojiler

- PHP 8.2
- SQLite
- HTML, CSS, Bootstrap
- Docker & Docker Compose
- FPDF (PDF üretimi)

## 🚀 Kurulum Talimatları:

1. Repo’yu klonlayın:

```bash
git clone https://github.com/iremsenss/bilet-satin-alma-platform.git
```

2. Proje dizinine gidin:

```bash
cd bilet-satin-alma-platform
```

3. Docker ile başlatın:

```bash
docker-compose up -d
```
4. Tarayıcıda açın:

```bash
http://localhost:8080
```

## 👤 Test Kullanıcı Bilgileri 

| Kullanıcı Rolü | E-posta Adresi | Şifre |
| :--- | :--- | :--- |
| Admin | admin@ticketbox.com | admin123 |
| Firma Admin | aliosmanulusoy@ticketbox.com | 123456 |
| Firma Admin | metro@ticketbox.com | 123456 |
| Firma Admin | varan@ticketbox.com | 123456 |
| Firma Admin | pamukkale@ticketbox.com | 123456 |
