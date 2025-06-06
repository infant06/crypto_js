document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("secureForm");
  const responseDiv = document.getElementById("responseMessage");

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
      // Show loading state
      responseDiv.innerHTML =
        '<div class="loading">Encrypting and sending data...</div>';

      // Wait for encryption to be initialized
      await waitForEncryption();

      // Collect form data
      const formData = {
        name: document.getElementById("name").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value,
        message: document.getElementById("message").value,
        sensitive_data: document.getElementById("sensitive_data").value,
        timestamp: Date.now(),
        nonce: window.secureEncryption.generateNonce(),
      };

      // Encrypt the form data
      const encryptedData = window.secureEncryption.encryptData(formData);

      // Create payload
      const payload = {
        encrypted_data: encryptedData,
        client_key: window.secureEncryption.clientKey,
        action: "submit",
      };

      // Send to API
      const response = await fetch("api.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json();

      if (result.success) {
        responseDiv.innerHTML =
          '<div class="success">Data submitted successfully! ID: ' +
          result.data_id +
          "</div>";
        form.reset();
      } else {
        responseDiv.innerHTML =
          '<div class="error">Error: ' + result.message + "</div>";
      }
    } catch (error) {
      console.error("Submission error:", error);
      responseDiv.innerHTML =
        '<div class="error">Failed to submit data. Please try again.</div>';
    }
  });
  async function waitForEncryption() {
    let attempts = 0;
    const maxAttempts = 50;

    while (!window.secureEncryption.sessionKey && attempts < maxAttempts) {
      await new Promise((resolve) => setTimeout(resolve, 100));
      attempts++;

      if (attempts % 10 === 0) {
        console.log(
          `Waiting for encryption initialization... Attempt ${attempts}/${maxAttempts}`
        );
      }
    }

    if (!window.secureEncryption.sessionKey) {
      console.error(
        "Encryption initialization failed after",
        maxAttempts,
        "attempts"
      );
      console.error("ClientKey:", window.secureEncryption.clientKey);
      console.error("SessionKey:", window.secureEncryption.sessionKey);
      throw new Error("Encryption initialization failed");
    }

    console.log("Encryption ready!");
  }
});
