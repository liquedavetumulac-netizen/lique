import { useState } from "react";
import {
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";

type CalcButtonProps = {
  label: string;
  onPress: () => void;
  type?: "number" | "operator" | "action" | "equal";
};

function Display({ expression, value }: { expression: string; value: string }) {
  return (
    <View style={styles.display}>
      <Text style={styles.expression}>{expression || " "}</Text>
      <Text style={styles.value} numberOfLines={1} adjustsFontSizeToFit>
        {value}
      </Text>
    </View>
  );
}

function CalcButton({ label, onPress, type = "number" }: CalcButtonProps) {
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        type === "operator" && styles.operatorButton,
        type === "action" && styles.actionButton,
        type === "equal" && styles.equalButton,
        pressed && styles.pressedButton,
      ]}
    >
      <Text
        style={[
          styles.buttonText,
          type !== "number" && styles.specialButtonText,
          type === "equal" && styles.equalButtonText,
        ]}
      >
        {label}
      </Text>
    </Pressable>
  );
}

export default function Calculator() {
  const [value, setValue] = useState("0");
  const [expression, setExpression] = useState("");
  const [storedValue, setStoredValue] = useState<number | null>(null);
  const [operator, setOperator] = useState("");
  const [waiting, setWaiting] = useState(false);
  const [history, setHistory] = useState<string[]>([]);

  function inputNumber(number: string) {
    if (waiting) {
      setValue(number);
      setWaiting(false);
      return;
    }

    setValue((current) => (current === "0" ? number : current + number));
  }

  function inputDecimal() {
    if (waiting) {
      setValue("0.");
      setWaiting(false);
      return;
    }

    if (!value.includes(".")) {
      setValue(value + ".");
    }
  }

  function clear() {
    setValue("0");
    setExpression("");
    setStoredValue(null);
    setOperator("");
    setWaiting(false);
  }

  function backspace() {
    if (waiting) return;

    setValue((current) => (current.length <= 1 ? "0" : current.slice(0, -1)));
  }

  function chooseOperator(nextOperator: string) {
    const currentNumber = Number(value);

    if (storedValue !== null && operator && !waiting) {
      const result = calculate(storedValue, currentNumber, operator);

      if (result === null) {
        setValue("Cannot divide by zero");
        setExpression("");
        setStoredValue(null);
        setOperator("");
        setWaiting(true);
        return;
      }

      setStoredValue(result);
      setValue(String(result));
      setExpression(`${result} ${nextOperator}`);
    } else {
      setStoredValue(currentNumber);
      setExpression(`${currentNumber} ${nextOperator}`);
    }

    setOperator(nextOperator);
    setWaiting(true);
  }

  function calculate(first: number, second: number, op: string): number | null {
    let result: number;

    switch (op) {
      case "+":
        result = first + second;
        break;
      case "−":
        result = first - second;
        break;
      case "×":
        result = first * second;
        break;
      case "÷":
        if (second === 0) return null;
        result = first / second;
        break;
      default:
        return second;
    }

    if (!Number.isFinite(result)) return null;
    return Number(result.toPrecision(12));
  }

  function equals() {
    if (storedValue === null || !operator || waiting) return;

    const first = storedValue;
    const second = Number(value);
    const result = calculate(first, second, operator);
    const oldExpression = `${first} ${operator} ${second} =`;

    if (result === null) {
      setExpression(oldExpression);
      setValue("Cannot divide by zero");
      setStoredValue(null);
      setOperator("");
      setWaiting(true);
      return;
    }

    setHistory((oldHistory) =>
      [`${oldExpression} ${result}`, ...oldHistory].slice(0, 5),
    );

    setExpression(oldExpression);
    setValue(String(result));
    setStoredValue(null);
    setOperator("");
    setWaiting(true);
  }

  function percentage() {
    setValue(String(Number(value) / 100));
    setWaiting(false);
  }

  function toggleSign() {
    if (Number(value) !== 0) {
      setValue(String(Number(value) * -1));
    }
  }

  const buttons: CalcButtonProps[] = [
    { label: "C", type: "action", onPress: clear },
    { label: "⌫", type: "action", onPress: backspace },
    { label: "%", type: "action", onPress: percentage },
    { label: "÷", type: "operator", onPress: () => chooseOperator("÷") },

    { label: "7", onPress: () => inputNumber("7") },
    { label: "8", onPress: () => inputNumber("8") },
    { label: "9", onPress: () => inputNumber("9") },
    { label: "×", type: "operator", onPress: () => chooseOperator("×") },

    { label: "4", onPress: () => inputNumber("4") },
    { label: "5", onPress: () => inputNumber("5") },
    { label: "6", onPress: () => inputNumber("6") },
    { label: "−", type: "operator", onPress: () => chooseOperator("−") },

    { label: "1", onPress: () => inputNumber("1") },
    { label: "2", onPress: () => inputNumber("2") },
    { label: "3", onPress: () => inputNumber("3") },
    { label: "+", type: "operator", onPress: () => chooseOperator("+") },

    { label: "±", type: "action", onPress: toggleSign },
    { label: "0", onPress: () => inputNumber("0") },
    { label: ".", onPress: inputDecimal },
    { label: "=", type: "equal", onPress: equals },
  ];

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.title}>Calculator</Text>
        </View>

        <Display expression={expression} value={value} />

        <View style={styles.keypad}>
          {buttons.map((button, index) => (
            <CalcButton
              key={`${button.label}-${index}`}
              label={button.label}
              onPress={button.onPress}
              type={button.type}
            />
          ))}
        </View>

        <View style={styles.historySection}>
          <Text style={styles.historyTitle}>History</Text>

          {history.length === 0 ? (
            <Text style={styles.emptyHistory}>No calculations yet.</Text>
          ) : (
            history.map((item, index) => (
              <Text key={`${item}-${index}`} style={styles.historyItem}>
                {item}
              </Text>
            ))
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F4F6FA",
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 16,
    maxWidth: 520,
    width: "100%",
    alignSelf: "center",
  },
  header: {
    alignItems: "center",
    marginBottom: 14,
  },
  title: {
    fontSize: 24,
    fontWeight: "700",
    color: "#182230",
  },
  display: {
    minHeight: 130,
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    padding: 18,
    justifyContent: "flex-end",
    alignItems: "flex-end",
    marginBottom: 14,
  },
  expression: {
    minHeight: 20,
    fontSize: 15,
    color: "#8B95A5",
    marginBottom: 4,
  },
  value: {
    width: "100%",
    textAlign: "right",
    fontSize: 40,
    fontWeight: "600",
    color: "#182230",
  },
  keypad: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-between",
    gap: 8,
  },
  button: {
    width: "22.5%",
    minHeight: 58,
    borderRadius: 14,
    backgroundColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
  },
  operatorButton: {
    backgroundColor: "#E3EDFF",
  },
  actionButton: {
    backgroundColor: "#E8ECF3",
  },
  equalButton: {
    backgroundColor: "#397BE7",
  },
  pressedButton: {
    opacity: 0.65,
    transform: [{ scale: 0.96 }],
  },
  buttonText: {
    fontSize: 21,
    fontWeight: "500",
    color: "#263247",
  },
  specialButtonText: {
    color: "#397BE7",
    fontWeight: "600",
  },
  equalButtonText: {
    color: "#FFFFFF",
  },
  historySection: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    padding: 14,
    marginTop: 16,
  },
  historyTitle: {
    fontSize: 15,
    fontWeight: "700",
    color: "#182230",
    marginBottom: 6,
  },
  emptyHistory: {
    fontSize: 12,
    color: "#8B95A5",
  },
  historyItem: {
    fontSize: 13,
    color: "#536174",
    paddingVertical: 5,
    borderBottomWidth: 1,
    borderBottomColor: "#F0F2F6",
  },
});
