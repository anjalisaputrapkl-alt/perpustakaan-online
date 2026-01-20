#!/bin/bash

# =====================================================
# Script Setup dan Maintenance Modul Notifikasi
# =====================================================

set -e

echo "🔔 Setup Modul Notifikasi Siswa"
echo "================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-perpustakaan_online}"
DB_HOST="${DB_HOST:-localhost}"
BACKUP_DIR="./backups"
SQL_FILE="sql/migrations/notifications.sql"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Helper functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Main menu
show_menu() {
    echo ""
    echo "Pilih aksi:"
    echo "1. Import database (buat tabel notifikasi)"
    echo "2. Insert sample data"
    echo "3. Backup database"
    echo "4. Restore database"
    echo "5. Check tabel notifikasi"
    echo "6. Cleanup notifikasi lama"
    echo "7. Generate random sample data"
    echo "8. Exit"
    echo ""
    read -p "Pilihan [1-8]: " choice
}

# 1. Import Database
import_database() {
    print_info "Importing notifikasi schema..."
    
    if [ ! -f "$SQL_FILE" ]; then
        print_error "File $SQL_FILE tidak ditemukan!"
        return 1
    fi
    
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$SQL_FILE"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE"
    fi
    
    if [ $? -eq 0 ]; then
        print_success "Database schema berhasil diimport!"
    else
        print_error "Gagal import database schema"
        return 1
    fi
}

# 2. Insert Sample Data
insert_sample_data() {
    print_info "Inserting sample data..."
    
    local sql="
    INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi, tanggal, status_baca) VALUES
    (1, 'Buku Telat Dikembalikan', 'Buku Clean Code belum dikembalikan. Tenggat: 2024-01-15. Denda: Rp 5.000/hari', 'telat', DATE_SUB(NOW(), INTERVAL 3 DAY), 0),
    (1, 'Peringatan: Denda Diperoleh', 'Anda telah dikenakan denda sebesar Rp 15.000 untuk keterlambatan pengembalian buku', 'peringatan', DATE_SUB(NOW(), INTERVAL 5 DAY), 1),
    (1, 'Notifikasi Pengembalian Buku', 'Jangan lupa mengembalikan buku Design Patterns sebelum tanggal 2024-01-20', 'pengembalian', DATE_SUB(NOW(), INTERVAL 1 DAY), 0),
    (1, 'Informasi Terbaru', 'Perpustakaan akan ditutup pada tanggal 25 Januari untuk pemeliharaan sistem', 'info', NOW(), 0),
    (1, 'Peminjaman Berhasil', 'Anda berhasil meminjam buku Refactoring pada 2024-01-10', 'sukses', DATE_SUB(NOW(), INTERVAL 7 DAY), 1),
    (1, 'Katalog Buku Baru', 'Ada 5 buku baru dalam katalog perpustakaan', 'buku', DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
    (2, 'Buku Siap Diambil', 'Buku yang Anda pesan Introduction to Algorithms sudah tersedia', 'info', NOW(), 0),
    (2, 'Peminjaman Berhasil', 'Anda berhasil meminjam 3 buku pada 2024-01-10', 'sukses', DATE_SUB(NOW(), INTERVAL 2 DAY), 1);
    "
    
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "$sql"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$sql"
    fi
    
    if [ $? -eq 0 ]; then
        print_success "Sample data berhasil diinsert!"
    else
        print_error "Gagal insert sample data"
        return 1
    fi
}

# 3. Backup Database
backup_database() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="$BACKUP_DIR/notifikasi_backup_$timestamp.sql"
    
    print_info "Backup database ke $backup_file..."
    
    if [ -z "$DB_PASS" ]; then
        mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" notifikasi > "$backup_file"
    else
        mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" notifikasi > "$backup_file"
    fi
    
    if [ -f "$backup_file" ]; then
        local size=$(du -h "$backup_file" | cut -f1)
        print_success "Backup berhasil: $backup_file ($size)"
    else
        print_error "Gagal membuat backup"
        return 1
    fi
}

# 4. Restore Database
restore_database() {
    echo ""
    echo "Daftar backup file:"
    ls -1h "$BACKUP_DIR"/*.sql 2>/dev/null | nl
    echo ""
    read -p "Pilih nomor file atau ketik path: " backup_choice
    
    if [ -f "$backup_choice" ]; then
        backup_file="$backup_choice"
    else
        backup_file=$(ls -1 "$BACKUP_DIR"/*.sql 2>/dev/null | sed -n "${backup_choice}p")
    fi
    
    if [ ! -f "$backup_file" ]; then
        print_error "File backup tidak ditemukan"
        return 1
    fi
    
    print_warning "Ini akan menghapus data notifikasi yang ada!"
    read -p "Lanjutkan? (y/n): " confirm
    
    if [ "$confirm" != "y" ]; then
        print_info "Restore dibatalkan"
        return 0
    fi
    
    print_info "Restoring dari $backup_file..."
    
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$backup_file"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$backup_file"
    fi
    
    if [ $? -eq 0 ]; then
        print_success "Restore berhasil!"
    else
        print_error "Gagal restore database"
        return 1
    fi
}

# 5. Check Table
check_table() {
    print_info "Checking table notifikasi..."
    
    local query="SELECT COUNT(*) as total, SUM(CASE WHEN status_baca=0 THEN 1 ELSE 0 END) as unread FROM notifikasi;"
    
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "$query"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$query"
    fi
    
    echo ""
    local structure_query="DESCRIBE notifikasi;"
    
    print_info "Table structure:"
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "$structure_query"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$structure_query"
    fi
}

# 6. Cleanup Old Data
cleanup_old_data() {
    read -p "Hapus notifikasi lebih lama dari berapa hari? [30]: " days
    days=${days:-30}
    
    local query="DELETE FROM notifikasi WHERE tanggal < DATE_SUB(NOW(), INTERVAL $days DAY);"
    
    print_warning "Ini akan menghapus ${days} hari notifikasi!"
    read -p "Lanjutkan? (y/n): " confirm
    
    if [ "$confirm" != "y" ]; then
        print_info "Cleanup dibatalkan"
        return 0
    fi
    
    print_info "Cleanup notifikasi lama..."
    
    if [ -z "$DB_PASS" ]; then
        result=$(mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "$query; SELECT ROW_COUNT();" 2>&1)
    else
        result=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$query; SELECT ROW_COUNT();" 2>&1)
    fi
    
    if [ $? -eq 0 ]; then
        print_success "Cleanup berhasil!"
        echo "Query output: $result"
    else
        print_error "Gagal cleanup"
        return 1
    fi
}

# 7. Generate Random Sample Data
generate_random_data() {
    read -p "Berapa data yang ingin digenerate? [10]: " count
    count=${count:-10}
    
    print_info "Generating $count sample data..."
    
    local jenis=("telat" "peringatan" "pengembalian" "info" "sukses" "buku")
    local judul=("Buku Telat" "Peringatan Denda" "Pengembalian Buku" "Info Sistem" "Peminjaman Sukses" "Buku Baru")
    local pesan=("Buku belum dikembalikan" "Denda dikenakan" "Jangan lupa kembalikan" "Update perpustakaan" "Peminjaman berhasil" "Buku baru tersedia")
    
    # Create SQL insert values
    local sql="INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi, tanggal, status_baca) VALUES "
    local values=""
    
    for ((i=1; i<=count; i++)); do
        local student=$((1 + RANDOM % 10))
        local j=$((RANDOM % 6))
        local days=$((RANDOM % 30))
        local status=$((RANDOM % 2))
        
        if [ $i -gt 1 ]; then
            values="$values, "
        fi
        
        values="$values($student, '${judul[$j]} #$i', '${pesan[$j]} - Sample data #$i', '${jenis[$j]}', DATE_SUB(NOW(), INTERVAL $days DAY), $status)"
    done
    
    local full_sql="${sql}${values};"
    
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "$full_sql"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$full_sql"
    fi
    
    if [ $? -eq 0 ]; then
        print_success "$count sample data berhasil dibuat!"
    else
        print_error "Gagal generate sample data"
        return 1
    fi
}

# Main loop
while true; do
    show_menu
    
    case $choice in
        1) import_database ;;
        2) insert_sample_data ;;
        3) backup_database ;;
        4) restore_database ;;
        5) check_table ;;
        6) cleanup_old_data ;;
        7) generate_random_data ;;
        8) echo -e "${GREEN}Selesai!${NC}"; exit 0 ;;
        *) print_error "Pilihan tidak valid" ;;
    esac
    
    read -p "Tekan Enter untuk melanjutkan..."
done
