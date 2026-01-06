<!-- Footer -->
<footer class="bg-nsknavy text-white py-4 mt-auto w-full">
    <div class="container mx-auto px-6">
        <div class="text-center">
            <p class="text-blue-200">
                &copy; 2023 Northland Schools Kano. All rights reserved.
            </p>
        </div>
    </div>
</footer>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global SweetAlert Helpers
        function confirmSubmit(event, title, text, confirmButtonText = 'Yes, do it!', confirmButtonColor = '#3085d6') {
            event.preventDefault();
            const form = event.target;
            
            Swal.fire({
                title: title || 'Are you sure?',
                text: text || "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        function confirmLink(event, title, text, confirmButtonText = 'Yes, do it!') {
            event.preventDefault(); 
            const link = event.currentTarget.href; 

            Swal.fire({
                title: title || 'Are you sure?',
                text: text || "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = link;
                }
            });
            return false;
        }
    </script>