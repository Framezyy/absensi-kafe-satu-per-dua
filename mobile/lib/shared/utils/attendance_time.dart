/// Parses API timestamps into an Asia/Jakarta wall-clock value.
///
/// Full timestamps are instants, so both `17:24+07:00` and `10:24Z`
/// must be displayed as `17:24` regardless of the device timezone.
DateTime? parseAttendanceTime(dynamic value) {
  if (value is DateTime) return toJakartaWallClock(value);
  if (value is! String || value.trim().isEmpty) return null;

  final text = value.trim();
  final parsed = DateTime.tryParse(text);
  if (parsed == null) return null;
  final hasExplicitTimezone =
      text.endsWith('Z') || RegExp(r'[+-]\d{2}:?\d{2}$').hasMatch(text);

  return hasExplicitTimezone ? toJakartaWallClock(parsed) : parsed;
}

DateTime toJakartaWallClock(DateTime value) {
  final utc = value.toUtc().add(const Duration(hours: 7));
  return DateTime(
    utc.year,
    utc.month,
    utc.day,
    utc.hour,
    utc.minute,
    utc.second,
    utc.millisecond,
    utc.microsecond,
  );
}

String formatAttendanceTime(DateTime? value) {
  if (value == null) return '-';
  return '${value.hour.toString().padLeft(2, '0')}:'
      '${value.minute.toString().padLeft(2, '0')}';
}
