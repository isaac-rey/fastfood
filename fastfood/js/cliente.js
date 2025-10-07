// Resaltar filas del carrito cuando cambian
document.addEventListener("DOMContentLoaded", () => {
  const inputs = document.querySelectorAll('input[type="number"]');
  inputs.forEach(input => {
    input.addEventListener("change", (e) => {
      const row = e.target.closest("tr");
      row.classList.add("highlight");
      setTimeout(() => row.classList.remove("highlight"), 800);
    });
  });

  // Confirmación antes de ir al checkout
  const checkoutBtn = document.querySelector('a[href="checkout_cliente.php"]');
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", (e) => {
      if (!confirm("¿Deseas confirmar este pedido?")) {
        e.preventDefault();
      }
    });
  }
});
