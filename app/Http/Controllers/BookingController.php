<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Booking;
use App\Models\BookedTimeSlot;
use App\Models\Review;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * ✅ Auto-cancel expired pending bookings (dipanggil di setiap request penting)
     */
    private function cancelExpiredPendingBookings()
    {
        try {
            $expirationTime = Carbon::now()->subMinutes(10);

            $expiredBookings = Booking::where('payment_status', 'pending')
                ->where('status', 'pending')
                ->where('created_at', '<', $expirationTime)
                ->get();

            foreach ($expiredBookings as $booking) {
                DB::beginTransaction();
                try {
                    $booking->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled'
                    ]);

                    // Hapus BookedTimeSlot yang terkait
                    BookedTimeSlot::where('booking_id', $booking->id)->delete();

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Failed to cancel expired booking: ' . $e->getMessage());
                }
            }

            return $expiredBookings->count();
        } catch (\Exception $e) {
            \Log::error('Error in cancelExpiredPendingBookings: ' . $e->getMessage());
            return 0;
        }
    }

    public function index(Request $request)
    {
        // ✅ Jalankan auto-cancel sebelum render halaman
        $this->cancelExpiredPendingBookings();

        $weekOffset = $request->get('week', 0);
        $selectedVenueType = $request->get('venue', 'pvj');

        $venues = [
            'cibadak_a' => [
                'id' => 1,
                'venue_type' => 'cibadak_a',
                'name' => 'The Arena Cibadak A',
                'location' => 'GG Nyi Empok No. 8, Kota Bandung',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena Cibadak berlokasi di GG Nyi Empok No. 8, Kota Bandung. The Arena Cibadak memiliki 2 lapangan basket indoor berstandar internasional dengan lantai kayu jati (Cibadak A) dan Vinyl (Cibadak B).',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan berstandar internasional dengan fasilitas lengkap dan lokasi strategis di Bandung.',
                'images' => [
                    'https://asset.ayo.co.id/image/venue/170003416347969.image_cropper_466C53BF-81BA-448E-8CF0-A3C934C85D3C-66032-000009DD206E034B_large.jpg',
                    'https://asset.ayo.co.id/image/venue/170003416477189.image_cropper_2BD1A5A7-0676-4452-9AA6-06E18062A969-66032-000009DD2B5826B2_large.jpg',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                ],
                'facilities' => [
                    'Café & Resto',
                    'Tribun Penonton',
                    'Parkir Mobil & Motor',
                    'Toilet',
                    'Penjualan makanan ringan & minuman',
                ],
                'rules' => [
                    'Dilarang meludah di area lapangan',
                    'Gunakan sepatu olahraga / basket',
                    'Dilarang membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu dan dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],

            'cibadak_b' => [
                'id' => 2,
                'venue_type' => 'cibadak_b',
                'name' => 'The Arena Cibadak B',
                'location' => 'GG Nyi Empok No. 8, Kota Bandung',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena Cibadak berlokasi di GG Nyi Empok No. 8, Kota Bandung. The Arena Cibadak memiliki 2 lapangan basket indoor berstandar internasional dengan lantai kayu jati (Cibadak A) dan Vinyl (Cibadak B).',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan berstandar internasional dengan fasilitas lengkap dan lokasi strategis di Bandung.',
                'images' => [
                    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR61KZvDDMVjUXnUkC4_nQOv7wZqAcQ5Jqy4A&s',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                ],
                'facilities' => [
                    'Café & Resto',
                    'Tribun Penonton',
                    'Parkir Mobil & Motor',
                    'Toilet',
                    'Penjualan makanan ringan & minuman',
                ],
                'rules' => [
                    'Dilarang meludah di area lapangan',
                    'Gunakan sepatu olahraga / basket',
                    'Dilarang membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu dan dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],

            'pvj' => [
                'id' => 3,
                'venue_type' => 'pvj',
                'name' => 'The Arena PVJ',
                'location' => 'Paris Van Java Mall, Lantai P13, Bandung',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena PVJ berlokasi di Paris Van Java Mall, Lantai P13, Bandung. Tersedia 1 lapangan basket indoor dengan material kayu jati berkualitas, memberikan pengalaman bermain yang optimal. Kami mengundang Anda untuk merasakan pengalaman berolahraga di fasilitas terbaik yang dapat disesuaikan dengan kebutuhan latihan maupun acara.',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan premium dengan material kayu jati berkualitas. Fasilitas lengkap dan lokasi strategis di pusat perbelanjaan membuat The Arena PVJ menjadi pilihan utama para pecinta basket di Bandung.',
                'images' => [
                    'https://asset.ayo.co.id/image/venue/170003800277035.image_cropper_57F0FE3D-3F2C-484F-81A3-B51A20E53D60-67054-000009F27EB246C9_large.jpg',
                    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS7hOrJUHTfqrHB6dwwmo47zIl-OXVCMoo9IA&s',
                    'https://asset.ayo.co.id/image/venue/170003399361601.image_cropper_246D9558-9BCD-4B7E-BD8B-BC836A3F7C4E-66032-000009DC36E81169_large.jpg',
                    'https://www.rajawaliparquet.com/wp-content/uploads/2025/06/lapangan-basket-the-arena-PVJ-bandung-3.jpg',
                    'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?w=1200',
                ],
                'facilities' => [
                    'Scoreboard',
                    'Shotclock',
                    'Sound System',
                    'Café & Resto',
                    'Tribun Penonton',
                    'Parkir Mobil & Motor',
                    'Toilet',
                    'Penjualan makanan ringan & minuman',
                ],
                'rules' => [
                    'Dilarang merokok',
                    'Dilarang meludah di area lapangan',
                    'Wajib menggunakan sepatu olahraga / basket',
                    'Dilarang membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu',
                    'Pemain harus dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],

            'urban' => [
                'id' => 4,
                'venue_type' => 'urban',
                'name' => 'The Arena Urban',
                'location' => 'Jl. Urban Complex No. 88, Bandung',
                'description' => 'Lapangan basket semi-outdoor dengan lantai vinyl',
                'full_description' => 'The Arena Urban merupakan lapangan basket semi-outdoor dengan lantai vinyl, dilengkapi seating area luas dan suasana yang nyaman. Cocok untuk bermain menonton, maupun bersantai.',
                'invitation' => 'Nikmati pengalaman bermain basket di arena semi-outdoor dengan suasana nyaman dan fasilitas lengkap untuk aktivitas olahraga dan rekreasi.',
                'images' => [
                    'https://asset.ayo.co.id/image/venue-field/176053273711314.image_cropper_FDE41FB2-3CAA-444D-9298-BF982C70FE61-8678-000002259DD23378.jpg.jpeg    ',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                    'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAREAAAC4CAMAAADzLiguAAAAPFBMVEX///+rq6unp6fMzMykpKTp6enx8fHU1NS0tLS6urr6+vqwsLDHx8fPz8/w8PD19fXa2trh4eHl5eXAwMAzrysnAAADpklEQVR4nO2c2ZKDIBAAE6KJmsPr//91c69yKKREHav7dctl6YVhGJTdDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZqE5LMU1XbrvVupELUe9dO9t5PsFyZfuvY1FjWRL994GRnQeRs5NOj+rNpIVCzSMER2M6GBEByM6GNHBiI4cI+mhbdtLE12SFCO3XKnH36ryJnLDQoxU/xm2usZtWIaRWu1nUyLCSNnfh6moE0eEkYvqK4lavpBgpNA368ktYsMSjKSJbqSK2LAEI7VuRB0iNizBSGUYuURsWIIRc4zEXH8lGDkacSTm6YEEI7tMX2zKiA2LMFL185HAMJJWdcj2UIQRfZCEDJEyT5JkH7BcyzBSnrujJORY9r0BSPzXaxlGHv/pz5TJQoQUn4Mw5T1KhBi5x5LseUadnYJKRlcVPLLEGNkVt7qq0rASWtOZa7nno3KM/EB5/mGF2rSRvLdqe+Z1WzZy0Moq6ujz1IaNNJoQz1CyXSO9IPIeJD5ZyXaN6KXIJx6hZLNGKpuQ/Xl8A7BVI6nNx+MAbPTJjRopjAKCdyjZqJHWOmeeSsay+W0asQcRv1CySSM3t4/7IGmHH96ikW8JwKHkNPj0Fo3o2bvBYCiRayRt84u1a/WYkOHfK9bISam92lvW0qOZvRvzZqgwINXI+5zP0rd8dIgMHxwLNdI4+zYaRF643y6QaaT4nxlaxtXo538O3LJlGmk7fetlXKW9/ybuUCLSSC8l7WZchTt7N5S4QolEI1pK2sm4Tt5C7mPLEUoEGjH3tZ++OUoAjkHiKAwINGIWx86vHxTjmUhPib0wIM+IZV/7DpOhn/bZjyvEGbHOjGffQoLIG1thQJoRV3HsFhZEXqjWolyaEUdKqvLyl89hbYUBYUbcKWlYVP1i7p5lGfFOSb05G9JlGfHZ14ZhZiWijFwnF2IJJZKM1NP7eKCFEkFGLEfbk5D1sxJBRvz3tWFohQE5Rk6etaAflPQKA2KMpJFGyJNuYUCKkdJ1tD0JXfVSjFjfj5mMbigRYmToaHsSJf+FARlGftjXhvJ9j1GEEef7MdOhvu8xijASN4i8lXy+dJNgxPhOLw7vL80FGDnO4uN7FCbAyGx3xb0KA+s3cpntysnkGUpWb6Q8zcjjP7B6I7ODEZ1VGznfjrNzW7WRfbIA6zayFBjRWeWtxhU3X+vUi92Ofoh9CR0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMA2+AN7/TZH3Ls1kQAAAABJRU5ErkJggg==',
                ],
                'facilities' => [
                    'Scoreboard',
                    'Shotclock',
                    'Sound System',
                    'Café & Resto',
                    'Tribun Penonton',
                    'Gym & Pilates',
                    'Ruang Ganti',
                    'Musholla',
                    'Wi-Fi',
                    'Parkir Motor',
                ],
                'rules' => [
                    'Gunakan sepatu olahraga',
                    'Dilarang meludah dan membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu dan dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],
        ];

        $venue = $venues[$selectedVenueType] ?? $venues['pvj'];
        $schedules = $this->generateSchedules($weekOffset);

        $reviews = Review::with('client:id,name,profile_image')
            ->approved()
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'client_name' => $review->client->name,
                    'client_profile_image' => $review->client->profile_image,
                    'rating' => $review->rating,
                    'rating_facilities' => $review->rating_facilities,
                    'rating_hospitality' => $review->rating_hospitality,
                    'rating_cleanliness' => $review->rating_cleanliness,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('HomePage/Booking/Booking', [
            'auth' => [
                'client' => Auth::guard('client')->user()
            ],
            'venue' => $venue,
            'venues' => $venues,
            'schedules' => $schedules,
            'currentWeek' => $weekOffset,
            'reviews' => $reviews,
        ]);
    }

    private function generateSchedules($weekOffset = 0)
    {
        $schedules = [];
        $startDate = Carbon::now()->startOfWeek()->addWeeks((int)$weekOffset);

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayName = $days[$date->dayOfWeek];

            $schedules[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $dayName,
                'date_number' => $date->format('d'),
                'month' => $date->format('F'),
                'year' => $date->format('Y'),
                'display_date' => $dayName . ', ' . $date->format('d F Y'),
                'is_past' => $date->lt(Carbon::today()),
            ];
        }

        return $schedules;
    }

    /**
     * ✅ Calculate dynamic price based on venue, date, and time
     */
    private function calculatePrice($venueType, $date, $timeSlot)
    {
        // Parse date to get day of week (0 = Minggu, 1 = Senin, ..., 6 = Sabtu)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]); // Sabtu & Minggu

        // Extract start hour from time slot (e.g., "06.00 - 08.00" -> 6)
        preg_match('/^(\d{2})\./', $timeSlot, $matches);
        $startHour = isset($matches[1]) ? (int)$matches[1] : 0;

        // Dynamic pricing for PVJ
        if ($venueType === 'pvj') {
            if ($isWeekend) {
                // Sabtu - Minggu
                if ($startHour >= 6 && $startHour < 16) {
                    return 700000; // 06:00 - 16:00
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000; // 16:00 - 20:00
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000; // 20:00 - 24:00
                }
            } else {
                // Senin - Jumat
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000; // 06:00 - 16:00
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000; // 16:00 - 20:00
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000; // 20:00 - 24:00
                }
            }
        }

        // Dynamic pricing for Cibadak A
        if ($venueType === 'cibadak_a') {
            if ($isWeekend) {
                // Sabtu - Minggu
                if ($startHour >= 6 && $startHour < 20) {
                    return 700000; // 06:00 - 20:00
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000; // 20:00 - 24:00
                }
            } else {
                // Senin - Jumat
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000; // 06:00 - 16:00
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 700000; // 16:00 - 24:00
                }
            }
        }

        // Dynamic pricing for Cibadak B
        if ($venueType === 'cibadak_b') {
            if ($isWeekend) {
                // Sabtu - Minggu
                if ($startHour >= 6 && $startHour < 20) {
                    return 550000; // 06:00 - 20:00
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000; // 20:00 - 24:00
                }
            } else {
                // Senin - Jumat
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000; // 06:00 - 16:00
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 550000; // 16:00 - 20:00
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000; // 20:00 - 24:00
                }
            }
        }

        // Dynamic pricing for Urban
        if ($venueType === 'urban') {
            if ($isWeekend) {
                // Sabtu - Minggu
                return 550000; // 06:00 - 24:00
            } else {
                // Senin - Jumat
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000; // 06:00 - 16:00
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 550000; // 16:00 - 24:00
                }
            }
        }

        return 350000; // Fallback
    }

    /**
     * ✅ UPDATED: Hanya slot yang SUDAH BAYAR yang dianggap booked
     */
    public function getTimeSlots(Request $request)
    {
        // ✅ Auto-cancel expired bookings sebelum cek slot
        $this->cancelExpiredPendingBookings();

        $date = $request->input('date');
        $venueType = $request->input('venue_type', 'pvj');

        $allTimeSlots = [
            ['time' => '06.00 - 08.00', 'duration' => 120],
            ['time' => '08.00 - 10.00', 'duration' => 120],
            ['time' => '10.00 - 12.00', 'duration' => 120],
            ['time' => '12.00 - 14.00', 'duration' => 120],
            ['time' => '14.00 - 16.00', 'duration' => 120],
            ['time' => '16.00 - 18.00', 'duration' => 120],
            ['time' => '18.00 - 20.00', 'duration' => 120],
            ['time' => '20.00 - 22.00', 'duration' => 120],
            ['time' => '22.00 - 00.00', 'duration' => 120],
        ];

        // ✅ PERBAIKAN: Hanya hitung slot yang payment_status = 'paid' ATAU status = 'confirmed'
        $bookedFromTimeSlots = BookedTimeSlot::where('date', $date)
            ->where('venue_type', $venueType)
            ->whereHas('booking', function ($query) {
                // ✅ HANYA yang sudah bayar ATAU confirmed yang dianggap booked
                $query->where(function ($q) {
                    $q->where('payment_status', 'paid')
                        ->orWhere('status', 'confirmed');
                });
            })
            ->pluck('time_slot')
            ->toArray();

        // ✅ PERBAIKAN: Sama untuk booking langsung
        $bookedFromBookings = Booking::where('booking_date', $date)
            ->where('venue_type', $venueType)
            ->where(function ($query) {
                // ✅ HANYA yang sudah bayar ATAU confirmed
                $query->where('payment_status', 'paid')
                    ->orWhere('status', 'confirmed');
            })
            ->get()
            ->flatMap(function ($booking) {
                return collect($booking->time_slots)->pluck('time');
            })
            ->unique()
            ->toArray();

        // ✅ Merge kedua hasil
        $bookedSlots = array_unique(array_merge($bookedFromTimeSlots, $bookedFromBookings));

        // ✅ Add dynamic pricing to each slot
        $timeSlots = array_map(function ($slot) use ($bookedSlots, $venueType, $date) {
            $slot['price'] = $this->calculatePrice($venueType, $date, $slot['time']);
            $slot['status'] = in_array($slot['time'], $bookedSlots) ? 'booked' : 'available';
            return $slot;
        }, $allTimeSlots);

        return response()->json([
            'success' => true,
            'time_slots' => $timeSlots,
        ]);
    }

    /**
     * ✅ UPDATED: Validate price + Cek konflik hanya dengan booking yang sudah terbayar
     */
    public function processBooking(Request $request)
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.time' => 'required|string',
            'time_slots.*.price' => 'required|numeric',
            'time_slots.*.duration' => 'required|numeric',
            'venue_type' => 'required|string|in:cibadak_a,cibadak_b,pvj,urban',
        ]);

        if (!Auth::guard('client')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu untuk melakukan booking.'
                ], 401);
            }
            return back()->withErrors([
                'message' => 'Silakan login terlebih dahulu untuk melakukan booking.'
            ]);
        }

        try {
            // ✅ Auto-cancel expired bookings sebelum proses
            $this->cancelExpiredPendingBookings();

            DB::beginTransaction();

            // ✅ Validate prices from client match server calculation
            foreach ($validated['time_slots'] as $slot) {
                $expectedPrice = $this->calculatePrice(
                    $validated['venue_type'],
                    $validated['date'],
                    $slot['time']
                );

                if ($slot['price'] != $expectedPrice) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Harga tidak sesuai. Silakan refresh halaman dan coba lagi.'
                    ], 422);
                }
            }

            $requestedSlots = array_column($validated['time_slots'], 'time');

            // ✅ PERBAIKAN: Cek konflik HANYA dengan booking yang sudah terbayar atau confirmed
            $alreadyBooked = BookedTimeSlot::where('date', $validated['date'])
                ->where('venue_type', $validated['venue_type'])
                ->whereIn('time_slot', $requestedSlots)
                ->whereHas('booking', function ($query) {
                    // ✅ HANYA yang sudah bayar ATAU confirmed
                    $query->where(function ($q) {
                        $q->where('payment_status', 'paid')
                            ->orWhere('status', 'confirmed');
                    });
                })
                ->exists();

            if ($alreadyBooked) {
                DB::rollBack();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maaf, ada slot waktu yang sudah dibooking oleh orang lain. Silakan pilih slot waktu lain.'
                    ], 422);
                }

                return back()->withErrors([
                    'message' => 'Maaf, ada slot waktu yang sudah dibooking oleh orang lain. Silakan pilih slot waktu lain.'
                ]);
            }

            $totalPrice = array_sum(array_column($validated['time_slots'], 'price'));

            $booking = Booking::create([
                'client_id' => Auth::guard('client')->id(),
                'venue_id' => $validated['venue_id'],
                'booking_date' => $validated['date'],
                'venue_type' => $validated['venue_type'],
                'time_slots' => $validated['time_slots'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($validated['time_slots'] as $slot) {
                BookedTimeSlot::create([
                    'booking_id' => $booking->id,
                    'date' => $validated['date'],
                    'time_slot' => $slot['time'],
                    'venue_type' => $validated['venue_type'],
                ]);
            }

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking berhasil! Silakan lanjutkan ke pembayaran dalam 30 menit.',
                    'booking_id' => $booking->id,
                    'redirect_to_profile' => true,
                ]);
            }

            return back()->with([
                'flash' => [
                    'success' => true,
                    'message' => 'Booking berhasil! Silakan lanjutkan ke pembayaran dalam 30 menit.',
                    'booking_id' => $booking->id,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memproses booking: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors([
                'message' => 'Terjadi kesalahan saat memproses booking: ' . $e->getMessage()
            ]);
        }
    }

    public function storeReview(Request $request)
    {
        $validated = $request->validate([
            'rating_facilities' => 'required|integer|min:1|max:5',
            'rating_hospitality' => 'required|integer|min:1|max:5',
            'rating_cleanliness' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000|min:10',
        ]);

        if (!Auth::guard('client')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        try {
            $completedBookingWithoutReview = Booking::where('client_id', Auth::guard('client')->id())
                ->completedWithoutReview()
                ->oldest('booking_date')
                ->first();

            if (!$completedBookingWithoutReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum memiliki booking yang selesai atau semua booking sudah direview.'
                ], 422);
            }

            $averageRating = round(
                ($validated['rating_facilities'] + $validated['rating_hospitality'] + $validated['rating_cleanliness']) / 3
            );

            $review = Review::create([
                'client_id' => Auth::guard('client')->id(),
                'booking_id' => $completedBookingWithoutReview->id,
                'rating' => $averageRating,
                'rating_facilities' => $validated['rating_facilities'],
                'rating_hospitality' => $validated['rating_hospitality'],
                'rating_cleanliness' => $validated['rating_cleanliness'],
                'comment' => $validated['comment'],
                'is_approved' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Ulasan Anda akan ditampilkan setelah diverifikasi oleh admin.',
                'review' => [
                    'id' => $review->id,
                    'client_name' => Auth::guard('client')->user()->name,
                    'rating' => $review->rating,
                    'rating_facilities' => $review->rating_facilities,
                    'rating_hospitality' => $review->rating_hospitality,
                    'rating_cleanliness' => $review->rating_cleanliness,
                    'comment' => $review->comment,
                    'created_at' => 'Baru saja',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReviews()
    {
        $reviews = Review::with('client:id,name,profile_image')
            ->approved()
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'client_name' => $review->client->name,
                    'client_profile_image' => $review->client->profile_image,
                    'rating' => $review->rating,
                    'rating_facilities' => $review->rating_facilities,
                    'rating_hospitality' => $review->rating_hospitality,
                    'rating_cleanliness' => $review->rating_cleanliness,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            });
        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }
}
