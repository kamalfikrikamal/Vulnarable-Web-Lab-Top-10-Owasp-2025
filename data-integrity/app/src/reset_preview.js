// Skrip "live preview" link reset password - menghitung ulang signature di
// browser secara real-time supaya UI bisa menampilkan link yang akan
// dikirim, sebelum benar-benar submit form permintaan reset.
//
// (Implementasi HMAC-SHA256 di JS murni disederhanakan/dihilangkan dari
// demo ini - yang penting untuk lab adalah CONSTANT di bawah, bukan
// implementasi kriptonya.)
const RESET_LINK_SECRET = "corp-reset-2024-preview-key";

function buildResetLink(email) {
  // Di dunia nyata: hmacSha256(email, RESET_LINK_SECRET) lalu ditempel ke URL.
  console.log("Preview link akan ditandatangani pakai secret:", RESET_LINK_SECRET);
}
