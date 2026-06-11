import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../auth/presentation/auth_controller.dart';

/// Layar Profil — Wireframe 3.9 (akan disempurnakan di Phase 1.2).
///
/// Sesuai keputusan plan #6d: Profil **tidak menampilkan informasi
/// apa pun terkait wajah/verifikasi/embedding**. Tidak ada tombol
/// "Daftar Ulang Wajah". Reset enrollment hanya bisa dilakukan admin
/// via dashboard web (AF-04).
class ProfilePage extends ConsumerWidget {
  const ProfilePage({super.key});

  Future<void> _handleLogout(BuildContext context, WidgetRef ref) async {
    await ref.read(authControllerProvider.notifier).logout();
    if (!context.mounted) return;
    final state = ref.read(authControllerProvider);
    if (state.hasError) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Gagal keluar, silakan coba lagi')),
      );
    }
    // Saat sukses, router otomatis pindah ke /login karena state user
    // berubah jadi null.
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final authState = ref.watch(authControllerProvider);
    final isLoggingOut = authState.isLoading;
    final df = DateFormat('d MMMM yyyy', 'id_ID');

    return Scaffold(
      appBar: AppBar(title: const Text('Profil')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _ProfileTile(label: 'Nama', value: user?.nama ?? '-'),
            _ProfileTile(label: 'NIK', value: user?.nik ?? '-'),
            _ProfileTile(label: 'Jabatan', value: user?.jabatan ?? '-'),
            _ProfileTile(
              label: 'Tanggal Bergabung',
              value: user != null ? df.format(user.tanggalBergabung) : '-',
            ),
            _ProfileTile(
              label: 'Status',
              value: (user?.statusAktif ?? false) ? 'Aktif' : 'Tidak Aktif',
            ),
            const SizedBox(height: 24),
            OutlinedButton.icon(
              onPressed: isLoggingOut ? null : () => _handleLogout(context, ref),
              icon: isLoggingOut
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.logout),
              label: Text(isLoggingOut ? 'Sedang keluar...' : 'Keluar'),
            ),
          ],
        ),
      ),
    );
  }
}

class _ProfileTile extends StatelessWidget {
  const _ProfileTile({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        title: Text(label, style: Theme.of(context).textTheme.bodySmall),
        subtitle: Text(value, style: Theme.of(context).textTheme.titleMedium),
      ),
    );
  }
}
