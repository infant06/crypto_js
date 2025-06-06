<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Data - CryptoJS Encryption</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <h1>Submitted Secure Data</h1>

        <div class="navigation">
            <a href="form.php" class="btn-nav">Submit New Data</a>
            <button id="refreshData" class="btn-nav">Refresh Data</button>
        </div>

        <div id="dataContainer" class="data-container">
            <div class="loading">Loading data...</div>
        </div>
    </div>

    <script>
        class DataViewer {
            constructor() {
                this.init();
            }

            async init() {
                await this.loadData();
                this.setupEventListeners();
            }

            async loadData() {
                const container = document.getElementById('dataContainer');

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'getData' })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.displayData(result.data || []);
                    } else {
                        container.innerHTML = '<div class="error">Error loading data: ' + result.message + '</div>';
                    }
                } catch (error) {
                    console.error('Error loading data:', error);
                    container.innerHTML = '<div class="error">Failed to load data</div>';
                }
            }

            displayData(data) {
                const container = document.getElementById('dataContainer');

                if (data.length === 0) {
                    container.innerHTML = '<div class="no-data">No data submitted yet. <a href="form.php">Submit some data</a></div>';
                    return;
                }

                let html = '<div class="data-list">';

                data.forEach((item, index) => {
                    const formData = item.data;
                    html += `
                        <div class="data-item">
                            <div class="data-header">
                                <h3>Entry #${index + 1}</h3>
                                <span class="data-id">ID: ${item.id}</span>
                                <span class="data-date">${item.created_at}</span>
                            </div>
                            <div class="data-content">
                                <div class="data-row">
                                    <label>Name:</label>
                                    <span>${this.sanitizeOutput(formData.name)}</span>
                                </div>
                                <div class="data-row">
                                    <label>Email:</label>
                                    <span>${this.sanitizeOutput(formData.email)}</span>
                                </div>
                                <div class="data-row">
                                    <label>Phone:</label>
                                    <span>${this.sanitizeOutput(formData.phone)}</span>
                                </div>
                                <div class="data-row">
                                    <label>Message:</label>
                                    <span>${this.sanitizeOutput(formData.message)}</span>
                                </div>
                                ${formData.sensitive_data ? `
                                <div class="data-row sensitive">
                                    <label>Sensitive Data:</label>
                                    <span class="sensitive-content">${this.maskSensitiveData(formData.sensitive_data)}</span>
                                    <button onclick="this.nextElementSibling.style.display='inline'; this.style.display='none';" class="btn-reveal">Reveal</button>
                                    <span class="full-sensitive" style="display:none;">${this.sanitizeOutput(formData.sensitive_data)}</span>
                                </div>
                                ` : ''}
                                <div class="data-row">
                                    <label>Submitted:</label>
                                    <span>${formData.submitted_at}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;
            }

            sanitizeOutput(text) {
                if (!text) return '';
                return text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            maskSensitiveData(data) {
                if (!data) return '';
                if (data.length <= 4) return '*'.repeat(data.length);
                return data.substring(0, 2) + '*'.repeat(data.length - 4) + data.substring(data.length - 2);
            }

            setupEventListeners() {
                document.getElementById('refreshData').addEventListener('click', () => {
                    this.loadData();
                });
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new DataViewer();
        });
    </script>
</body>

</html>