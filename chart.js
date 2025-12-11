<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // ... kode filterData, openAddModal, dll ...

    // Data dari PHP (Pastikan kode PHP ini sudah ada sebelum script tag)
    const summary = <?= json_encode($summary) ?>; 
    
    // Pastikan kode ini dijalankan setelah elemen <canvas> dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const ctxElement = document.getElementById('kinerjaChart');
        
        // Cek apakah elemen <canvas> ditemukan
        if (ctxElement) {
            const ctx = ctxElement.getContext('2d');
            
            // Cek apakah data summary memiliki nilai numerik yang valid (misalnya, Total Target)
            const totalTarget = parseInt(summary.total_target) || 0;
            const totalRealisasi = parseInt(summary.total_realisasi) || 0;

            new Chart(ctx, {
                type: 'bar', // atau 'doughnut'
                data: {
                    labels: ['Total Target', 'Total Realisasi'],
                    datasets: [{
                        label: 'Kinerja Bulanan',
                        data: [totalTarget, totalRealisasi], // Gunakan data yang sudah divalidasi
                        backgroundColor: ['#f59e0b', '#8b5cf6'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    // Tambahkan opsi yang hilang atau kustomisasi lebih lanjut
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true // Tampilkan legend
                        }
                    }
                }
            });
        } else {
            console.error("Elemen canvas dengan ID 'kinerjaChart' tidak ditemukan.");
        }
    });
</script>