/**
 * api.js
 * Reusable jQuery API helper for GET, POST, PUT, PATCH, DELETE requests
 * Supports JSON, FormData, and optional JWT from cookie
 */

const protocol = window.location.protocol;
const hostname = window.location.hostname;
const port = window.location.port;
const hostUrl = window.location.origin;

const HOST_ROUTE_PATH = "/app"; // Adjust if needed
const BASE_API_URL = hostUrl + HOST_ROUTE_PATH + "/backend/public";

/**
 * Get cookie value by name
 * @param {string} name
 * @returns {string|null}
 */
function getCookie(name) {
  const value = "; " + document.cookie;
  const parts = value.split("; " + name + "=");
  if (parts.length === 2) return parts.pop().split(";").shift();
  return null;
}

/**
 * Generic API request
 * @param {string} method HTTP method ('GET','POST','PUT','PATCH','DELETE')
 * @param {string} url API endpoint
 * @param {Object|FormData|null} data Payload data
 * @param {boolean} isFormData true if sending multipart/form-data
 * @param {function} onSuccess Callback function on success
 * @param {function} onError Callback function on error
 */
function apiRequest(
  method,
  url,
  data = null,
  isFormData = false,
  onSuccess = null,
  onError = null
) {
  const httpMethod = method.toUpperCase();
  let fullUrl = BASE_API_URL + url;

  // For GET requests, append query params
  if (httpMethod === "GET" && data && typeof data === "object") {
    const queryString = new URLSearchParams(data).toString();
    fullUrl += "?" + queryString;
  }

  const ajaxOptions = {
    url: fullUrl,
    type: httpMethod,
    dataType: "json",
    success: function (response) {
      if (onSuccess) onSuccess(response);
    },
    error: function (xhr, status, error) {
      if (onError) onError(xhr, status, error);
    },
  };

  // Only attach payload if data exists
  if (data !== null && !["GET"].includes(httpMethod)) {
    if (isFormData) {
      ajaxOptions.data = data;
      ajaxOptions.processData = false;
      ajaxOptions.contentType = false;
    } else {
      ajaxOptions.data = JSON.stringify(data) ?? null;
      ajaxOptions.contentType = "application/json";
    }
  }

  $.ajax(ajaxOptions);
}

/** ===========================
 * Example Usage
 * =========================== */

// 1️⃣ GET request
// apiRequest(
//   "GET",
//   "/users",
//   { s: "John", sts: "active", limit: 2 },
//   false,
//   function (res) {
//     console.log("GET Success:", res);
//   },
//   function (xhr, status, err) {
//     console.error("GET Error:", err);
//   }
// );

// // 2️⃣ POST request (JSON)
// apiRequest(
//   "POST",
//   "/users",
//   { name: "John Doe", email: "john@example.com" },
//   false,
//   function (res) {
//     console.log("POST Success:", res);
//   },
//   function (xhr, status, err) {
//     console.error("POST Error:", err);
//   }
// );

// // 3️⃣ POST request (FormData / file upload)
// const formData = new FormData();
// formData.append("name", "Jane Doe");
// formData.append("avatar", document.querySelector("#avatarInput").files[0]);

// apiRequest(
//   "POST",
//   "/users/upload",
//   formData,
//   true,
//   function (res) {
//     console.log("Upload Success:", res);
//   },
//   function (xhr, status, err) {
//     console.error("Upload Error:", err);
//   }
// );

// // 4️⃣ PUT request
// apiRequest(
//   "PUT",
//   "/users/123",
//   { name: "John Updated" },
//   false,
//   function (res) {
//     console.log("PUT Success:", res);
//   },
//   function (xhr, status, err) {
//     console.error("PUT Error:", err);
//   }
// );

// // 5️⃣ PATCH request
// apiRequest(
//   "PATCH",
//   "/users/123",
//   { email: "john.new@example.com" },
//   false,
//   function (res) {
//     console.log("PATCH Success:", res);
//   },
//   function (xhr, status, err) {
//     console.error("PATCH Error:", err);
//   }
// );

// // 6️⃣ DELETE request
// apiRequest(
//   "DELETE",
//   "/users/123",
//   null,
//   false,
//   function (res) {
//     console.log("DELETE Success:", res);
//   },
//   function (xhr, status, err) {
//     console.error("DELETE Error:", err);
//   }
// );

function formatUnix(unix, format = "Y-m-d h:i:s") {
  const date = new Date(unix * 1000); // Convert seconds → ms
  const pad = (n) => String(n).padStart(2, "0");

  let hours24 = date.getHours();
  let hours12 = hours24 % 12 || 12; // Convert 0 → 12
  let ampm = hours24 >= 12 ? "PM" : "AM";

  const map = {
    Y: date.getFullYear(),
    m: pad(date.getMonth() + 1),
    d: pad(date.getDate()),
    H: pad(hours24), // 24hr
    h: pad(hours12), // 12hr
    i: pad(date.getMinutes()),
    s: pad(date.getSeconds()),
    A: ampm,
  };

  return format.replace(/Y|m|d|H|h|i|s|A/g, (match) => map[match]);
}

function getAge(date = null, dateInputId = null, outputId = null) {
  let dob;

  // 1. If date provided directly
  if (date) {
    dob = new Date(date);
  }
  // 2. If input tag id is provided
  else if (dateInputId) {
    const inputEl = document.getElementById(dateInputId);
    if (!inputEl || !inputEl.value) return null;
    dob = new Date(inputEl.value);
  } else {
    return null; // no input, no date → nothing to do
  }

  // Calculate age
  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const monthDiff = today.getMonth() - dob.getMonth();
  const dayDiff = today.getDate() - dob.getDate();

  if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
    age--;
  }

  // If outputId is given, set textContent or value depending on element type
  if (outputId) {
    const outputEl = document.getElementById(outputId);
    if (outputEl) {
      if (outputEl.tagName === "INPUT" || outputEl.tagName === "TEXTAREA") {
        outputEl.value = age; // for form inputs
      } else {
        outputEl.textContent = age; // for spans/divs/etc.
      }
    }
  }

  return age;
}

function formatDate(date, format = "YYYY-MM-DD HH:mm:ss") {
  const d = new Date(date);

  const map = {
    YYYY: d.getFullYear(),
    MM: String(d.getMonth() + 1).padStart(2, "0"),
    DD: String(d.getDate()).padStart(2, "0"),
    HH: String(d.getHours()).padStart(2, "0"),
    mm: String(d.getMinutes()).padStart(2, "0"),
    ss: String(d.getSeconds()).padStart(2, "0"),
    A: d.getHours() >= 12 ? "PM" : "AM",
  };

  return format.replace(/YYYY|MM|DD|HH|mm|ss|A/g, (key) => map[key]);
}

// ==== Helper Toast ====
function showToast(icon, text) {
  Swal.fire({
    toast: true,
    icon,
    text,
    position: "top-end",
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true,
  });
}

// Function to set select value (case-insensitive)
function setSelectValue(selectId, value) {
  const selectEl = document.getElementById(selectId);
  if (!selectEl) return;

  const normalizedValue = (value || "").toString().trim().toLowerCase();
  let matched = false;

  for (let option of selectEl.options) {
    if (option.value.trim().toLowerCase() === normalizedValue) {
      selectEl.value = option.value;
      matched = true;
      break;
    }
  }

  if (!matched) selectEl.value = "";
}

function formatTimestamp(date, format = "YYYY-MM-DD HH:mm:ss") {
  const d = new Date(date);

  const map = {
    YYYY: d.getFullYear(),
    MM: String(d.getMonth() + 1).padStart(2, "0"),
    DD: String(d.getDate()).padStart(2, "0"),
    HH: String(d.getHours()).padStart(2, "0"),
    mm: String(d.getMinutes()).padStart(2, "0"),
    ss: String(d.getSeconds()).padStart(2, "0"),
    A: d.getHours() >= 12 ? "PM" : "AM",
  };

  return format.replace(/YYYY|MM|DD|HH|mm|ss|A/g, (key) => map[key]);
}

/**
 * 🧰 Common String Utilities
 * Reusable functions for trimming, formatting, validating, and manipulating strings.
 */

const StringUtils = {
  /**
   * Trim and remove extra internal spaces.
   * Example: "  hello   world " → "hello world"
   */
  clean(str) {
    return (str || "").replace(/\s+/g, " ").trim();
  },

  /**
   * Convert string to Title Case.
   * Example: "hello world" → "Hello World"
   */
  toTitleCase(str) {
    return this.clean(str)
      .toLowerCase()
      .replace(/\b\w/g, (ch) => ch.toUpperCase());
  },

  /**
   * Convert string to snake_case.
   * Example: "Hello World" → "hello_world"
   */
  toSnakeCase(str) {
    return this.clean(str)
      .replace(/\s+/g, "_")
      .replace(/[A-Z]/g, (ch) => `_${ch.toLowerCase()}`)
      .replace(/^_+/, "")
      .toLowerCase();
  },

  /**
   * Convert string to kebab-case.
   * Example: "Hello World" → "hello-world"
   */
  toKebabCase(str) {
    return this.clean(str)
      .replace(/\s+/g, "-")
      .replace(/[A-Z]/g, (ch) => `-${ch.toLowerCase()}`)
      .replace(/^-+/, "")
      .toLowerCase();
  },

  /**
   * Capitalize the first character only.
   * Example: "hello" → "Hello"
   */
  capitalize(str) {
    str = this.clean(str);
    return str.charAt(0).toUpperCase() + str.slice(1);
  },

  /**
   * Shorten long strings and add ellipsis.
   * Example: "abcdefg", 5 → "ab..."
   */
  truncate(str, length = 50) {
    str = String(str);
    return str.length > length ? str.substring(0, length - 3) + "..." : str;
  },

  /**
   * Check if string is empty or only spaces.
   */
  isEmpty(str) {
    return !str || !str.trim();
  },

  /**
   * Extract only numbers from a string.
   * Example: "Phone: 987-654-3210" → "9876543210"
   */
  extractNumbers(str) {
    return (str || "").replace(/\D+/g, "");
  },

  /**
   * Check if valid phone number (10 digits).
   */
  isValidPhone(str) {
    return /^\d{10}$/.test(this.extractNumbers(str));
  },

  /**
   * Check if valid email address.
   */
  isValidEmail(str) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
  },

  /**
   * Remove HTML tags.
   */
  stripHTML(str) {
    return (str || "").replace(/<[^>]*>/g, "");
  },

  /**
   * Replace all case-insensitive matches of a substring.
   */
  replaceAll(str, find, replaceWith) {
    const regex = new RegExp(find, "gi");
    return (str || "").replace(regex, replaceWith);
  },
};

// 🧩 Global timeout storage object
const debounceTimers = {};

/**
 * Runs a function after a delay, uniquely identified by a key.
 * If called again before the delay finishes, it resets the timer.
 *
 * @param {string} key - Unique identifier for the timer
 * @param {function} callback - Function to execute after delay
 * @param {number} delay - Delay time in ms
 */
function setUniqueTimeout(key, callback, delay = 400) {
  // Clear any existing timer for this key
  if (debounceTimers[key]) {
    clearTimeout(debounceTimers[key]);
  }

  // Start a new timer
  debounceTimers[key] = setTimeout(() => {
    callback();
    delete debounceTimers[key]; // cleanup
  }, delay);
}
