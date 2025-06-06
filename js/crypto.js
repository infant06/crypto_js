// Secure encryption utilities
class SecureEncryption {
  constructor() {
    this.clientKey = null;
    this.sessionKey = null;
    this.init();
  }
  async init() {
    // Get encryption key from server without exposing it
    try {
      const response = await fetch("api.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ action: "getKey" }),
      });

      const keyData = await response.json();
      if (keyData.success) {
        this.clientKey = keyData.clientKey;
        this.sessionKey = keyData.sessionKey;
        console.log("Encryption initialized successfully");
      } else {
        console.error("Failed to get encryption keys:", keyData.message);
      }
    } catch (error) {
      console.error("Failed to initialize encryption:", error);
    }
  }
  encryptData(data) {
    if (!this.sessionKey) {
      throw new Error("Encryption not initialized");
    }

    // Convert data to string if it's an object
    const dataString = typeof data === "object" ? JSON.stringify(data) : data;

    // Generate random IV for each encryption
    const iv = CryptoJS.lib.WordArray.random(16);

    // Convert session key from hex to WordArray for proper encryption
    const keyWordArray = CryptoJS.enc.Hex.parse(this.sessionKey);

    // Encrypt using AES-256-CBC
    const encrypted = CryptoJS.AES.encrypt(dataString, keyWordArray, {
      iv: iv,
      mode: CryptoJS.mode.CBC,
      padding: CryptoJS.pad.Pkcs7,
    });

    // Combine IV and encrypted data
    const combined = iv.concat(encrypted.ciphertext);

    return CryptoJS.enc.Base64.stringify(combined);
  }

  // Generate a secure random string for additional entropy
  generateNonce() {
    return CryptoJS.lib.WordArray.random(16).toString();
  }

  // Hash function for additional security layers
  hashData(data) {
    return CryptoJS.SHA256(data).toString();
  }
}

// Global encryption instance
window.secureEncryption = new SecureEncryption();
