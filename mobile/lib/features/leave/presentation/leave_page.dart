import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/empty_state.dart';
import '../data/api_leave_repository.dart';
import '../domain/leave_request.dart';

/// Layar Pengajuan Izin — Wireframe 3.8.
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

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light.copyWith(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
      child: Scaffold(
        backgroundColor: const Color(0xFFF7F5F2),
        body: Column(
          children: [
            // Header
            Container(
              padding: EdgeInsets.fromLTRB(20, MediaQuery.paddingOf(context).top + 16, 20, 20),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF3D2314), Color(0xFF6F4E37)],
                ),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
              ),
              child: const Align(
                alignment: Alignment.centerLeft,
                child: Text('Pengajuan Izin', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: Colors.white)),
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  // Form card
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4))],
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 36, height: 36,
                                decoration: BoxDecoration(color: AppColors.warning.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
                                child: const Icon(Icons.edit_calendar_rounded, color: AppColors.warning, size: 20),
                              ),
                              const SizedBox(width: 12),
                              const Text('Ajukan Izin', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                            ],
                          ),
                          const SizedBox(height: 18),
                          _DateField(label: 'Tanggal Mulai', value: _tanggalMulai != null ? df.format(_tanggalMulai!) : null, onTap: () => _pickDate(isMulai: true)),
                          const SizedBox(height: 12),
                          _DateField(label: 'Tanggal Selesai (opsional)', value: _tanggalSelesai != null ? df.format(_tanggalSelesai!) : null, onTap: () => _pickDate(isMulai: false)),
                          const SizedBox(height: 12),
                          TextFormField(
                            controller: _alasanCtrl,
                            maxLines: 3,
                            decoration: const InputDecoration(labelText: 'Alasan', hintText: 'Contoh: Ada acara keluarga', alignLabelWithHint: true),
                            validator: (v) => (v == null || v.trim().isEmpty) ? 'Alasan wajib diisi' : null,
                          ),
                          const SizedBox(height: 20),
                          SizedBox(
                            height: 50,
                            child: FilledButton(
                              onPressed: _submitting ? null : _submit,
                              style: FilledButton.styleFrom(
                                backgroundColor: AppColors.primary,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              ),
                              child: _submitting
                                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(Icons.send_rounded, size: 18),
                                        SizedBox(width: 8),
                                        Text('Kirim Pengajuan', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
                                      ],
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text('Riwayat Izin', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                  const SizedBox(height: 12),
                  if (_loading)
                    const Padding(padding: EdgeInsets.all(20), child: Center(child: CircularProgressIndicator()))
                  else if (_leaves.isEmpty)
                    const EmptyState(icon: Icons.event_note, message: 'Belum ada pengajuan izin')
                  else
                    ...List.generate(_leaves.length, (i) {
                      final l = _leaves[i];
                      return _LeaveTile(leave: l, df: df);
                    }),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LeaveTile extends StatelessWidget {
  const _LeaveTile({required this.leave, required this.df});
  final LeaveRequest leave;
  final DateFormat df;

  @override
  Widget build(BuildContext context) {
    final (color, icon, label) = switch (leave.status) {
      LeaveStatus.pending => (AppColors.warning, Icons.hourglass_top_rounded, 'Menunggu'),
      LeaveStatus.approved => (AppColors.success, Icons.check_circle_rounded, 'Disetujui'),
      LeaveStatus.rejected => (AppColors.error, Icons.cancel_rounded, 'Ditolak'),
    };
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 44, height: 44,
            decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
            child: Icon(icon, color: color, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${df.format(leave.tanggalMulai)}'
                  '${leave.tanggalSelesai != leave.tanggalMulai ? " – ${df.format(leave.tanggalSelesai)}" : ""}',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                ),
                const SizedBox(height: 3),
                Text(leave.alasan, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(8)),
            child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
          ),
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
        decoration: InputDecoration(labelText: label, suffixIcon: const Icon(Icons.calendar_today_rounded, size: 18)),
        child: Text(value ?? 'Pilih tanggal', style: TextStyle(color: value != null ? AppColors.textPrimary : AppColors.textSecondary)),
      ),
    );
  }
}