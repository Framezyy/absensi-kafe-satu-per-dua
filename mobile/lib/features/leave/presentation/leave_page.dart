import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/empty_state.dart';
import '../data/api_leave_repository.dart';
import '../domain/leave_request.dart';

/// Layar Pengajuan Izin — Wireframe 3.8.
///
/// Menggunakan API Laravel: `GET /leaves`, `POST /leaves`.
class LeavePage extends ConsumerStatefulWidget {
  const LeavePage({super.key});

  @override
  ConsumerState<LeavePage> createState() => _LeavePageState();
}

class _LeavePageState extends ConsumerState<LeavePage> {
  final _formKey = GlobalKey<FormState>();
  final _alasanCtrl = TextEditingController();
  DateTime? _tanggalMulai;
  DateTime? _tanggalSelesai;
  bool _submitting = false;
  List<LeaveRequest> _leaves = [];
  bool _loading = true;
  final _repo = ApiLeaveRepository();

  @override
  void initState() {
    super.initState();
    _loadLeaves();
  }

  Future<void> _loadLeaves() async {
    setState(() => _loading = true);
    try {
      final data = await _repo.getMyLeaves();
      if (!mounted) return;
      setState(() {
        _leaves = data;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _alasanCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickDate({required bool isMulai}) async {
    final now = DateTime.now();
    final initial = isMulai ? (_tanggalMulai ?? now) : (_tanggalSelesai ?? _tanggalMulai ?? now);
    final date = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (date == null) return;
    setState(() {
      if (isMulai) {
        _tanggalMulai = date;
        if (_tanggalSelesai != null && _tanggalSelesai!.isBefore(date)) {
          _tanggalSelesai = null;
        }
      } else {
        _tanggalSelesai = date;
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_tanggalMulai == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilih tanggal izin terlebih dahulu')),
      );
      return;
    }
    setState(() => _submitting = true);
    try {
      await _repo.submit(
        tanggalMulai: _tanggalMulai!,
        tanggalSelesai: _tanggalSelesai,
        alasan: _alasanCtrl.text.trim(),
      );
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _tanggalMulai = null;
        _tanggalSelesai = null;
        _alasanCtrl.clear();
        _formKey.currentState!.reset();
      });
      _loadLeaves();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pengajuan izin berhasil dikirim')),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal mengirim: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('d MMM yyyy', 'id_ID');

    return Scaffold(
      appBar: AppBar(title: const Text('Pengajuan Izin')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text('Ajukan Izin', style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: 16),
                    _DateField(label: 'Tanggal Mulai', value: _tanggalMulai != null ? df.format(_tanggalMulai!) : null, onTap: () => _pickDate(isMulai: true)),
                    const SizedBox(height: 12),
                    _DateField(label: 'Tanggal Selesai (opsional)', value: _tanggalSelesai != null ? df.format(_tanggalSelesai!) : null, onTap: () => _pickDate(isMulai: false)),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _alasanCtrl,
                      maxLines: 3,
                      decoration: const InputDecoration(labelText: 'Alasan', alignLabelWithHint: true),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Alasan wajib diisi' : null,
                    ),
                    const SizedBox(height: 20),
                    FilledButton(
                      onPressed: _submitting ? null : _submit,
                      child: _submitting
                          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Kirim Pengajuan'),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 24),
          Text('Riwayat Izin', style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 12),
          if (_loading)
            const Center(child: CircularProgressIndicator())
          else if (_leaves.isEmpty)
            const EmptyState(icon: Icons.event_note, message: 'Belum ada pengajuan izin')
          else
            ...List.generate(_leaves.length, (i) {
              final l = _leaves[i];
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  leading: _StatusBadge(status: l.status),
                  title: Text(
                    '${df.format(l.tanggalMulai)}'
                    '${l.tanggalSelesai != l.tanggalMulai ? ' – ${df.format(l.tanggalSelesai)}' : ''}',
                  ),
                  subtitle: Text(l.alasan, maxLines: 2, overflow: TextOverflow.ellipsis),
                ),
              );
            }),
        ],
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({required this.label, required this.value, this.onTap});
  final String label;
  final String? value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: InputDecorator(
        decoration: InputDecoration(labelText: label, suffixIcon: const Icon(Icons.calendar_today, size: 20)),
        child: Text(value ?? 'Pilih tanggal', style: TextStyle(color: value != null ? null : AppColors.textSecondary)),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});
  final LeaveStatus status;

  @override
  Widget build(BuildContext context) {
    final (color, icon) = switch (status) {
      LeaveStatus.pending => (AppColors.warning, Icons.hourglass_top),
      LeaveStatus.approved => (AppColors.success, Icons.check_circle),
      LeaveStatus.rejected => (AppColors.error, Icons.cancel),
    };
    return Container(
      width: 40, height: 40,
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), shape: BoxShape.circle),
      child: Icon(icon, color: color, size: 22),
    );
  }
}
